<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use App\Services\UploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/** CSV import and export. Files are UTF-8 with a BOM so Excel shows Bangla correctly. */
class ProductBulkController extends Controller
{
    public const COLUMNS = [
        'name', 'category_ids', 'brand_id', 'unit', 'weight', 'min_qty', 'tags', 'barcode',
        'unit_price', 'sale_price', 'discount', 'discount_type', 'qty', 'sku',
        'description', 'meta_title', 'meta_description',
        'payment_policy', 'partial_payment_type', 'partial_advance_amount', 'thumbnail_url', 'published',
    ];

    public function __construct(private ProductService $products, private UploadService $uploads) {}

    public function importForm(): View
    {
        return view('admin.products.import', ['columns' => self::COLUMNS]);
    }

    public function template(): StreamedResponse
    {
        return $this->csv('product_import_template.csv', self::COLUMNS, [[
            'Matte Lipstick Rose Red', '1', '', 'Pc', '0.05', '1', 'lipstick,matte', '',
            '250', '', '20', 'percent', '50', 'LIP-001',
            '<p>Long lasting matte lipstick</p>', '', '',
            'full_cod', 'flat', '0', 'https://example.com/lipstick.jpg', '1',
        ]]);
    }

    public function categoryIds(): StreamedResponse
    {
        $rows = Category::tree()->map(fn ($c) => [$c->id, str_repeat('-- ', $c->depth).$c->name])->all();

        return $this->csv('category_ids.csv', ['id', 'name'], $rows);
    }

    public function brandIds(): StreamedResponse
    {
        return $this->csv('brand_ids.csv', ['id', 'name'], Brand::query()->orderBy('name')->get(['id', 'name'])->map(fn ($b) => [$b->id, $b->name])->all());
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:10240']]);
        $download = $request->boolean('download_images');

        [$header, $rows] = $this->readCsv($request->file('file'));
        $missing = array_diff(['name', 'category_ids', 'unit_price'], $header);
        if ($missing) {
            return back()->with('error', __('Missing columns: :cols', ['cols' => implode(', ', $missing)]));
        }

        $created = 0;
        $errors = [];
        foreach ($rows as $i => $values) {
            $line = $i + 2; // header is line 1
            $row = array_combine($header, array_pad(array_slice($values, 0, count($header)), count($header), ''));
            $row['category_ids'] = array_filter(array_map('intval', preg_split('/[|,]/', (string) $row['category_ids'])));

            $v = Validator::make($row, [
                'name' => ['required', 'string', 'max:255'],
                'category_ids' => ['required', 'array', 'min:1'],
                'category_ids.*' => [Rule::exists('categories', 'id')],
                'brand_id' => ['nullable', 'integer', Rule::exists('brands', 'id')],
                'unit_price' => ['required', 'numeric', 'min:0'],
                'sale_price' => ['nullable', 'numeric', 'min:0'],
                'discount' => ['nullable', 'numeric', 'min:0'],
                'discount_type' => ['nullable', 'in:flat,percent'],
                'qty' => ['nullable', 'integer'],
                'min_qty' => ['nullable', 'integer', 'min:1'],
                'barcode' => ['nullable', 'max:40', Rule::unique('products', 'barcode')],
                'payment_policy' => ['nullable', Rule::in(array_keys(Product::PAYMENT_POLICIES))],
                'partial_payment_type' => ['nullable', 'in:flat,percent'],
                'thumbnail_url' => ['nullable', 'url'],
            ]);
            if ($v->fails()) {
                $errors[] = __('Row :line: :error', ['line' => $line, 'error' => $v->errors()->first()]);

                continue;
            }

            $val = fn (string $key) => trim((string) ($row[$key] ?? '')) === '' ? null : trim((string) $row[$key]);
            $data = [
                'name' => $row['name'],
                'category_ids' => $row['category_ids'],
                'brand_id' => $val('brand_id'),
                'unit' => $val('unit'),
                'weight' => $val('weight'),
                'min_qty' => (int) ($row['min_qty'] ?? 1) ?: 1,
                'tags' => $val('tags'),
                'barcode' => $val('barcode'),
                'unit_price' => $row['unit_price'],
                'sale_price' => $val('sale_price'),
                'discount' => (float) ($row['discount'] ?? 0),
                'discount_type' => $val('discount_type') ?? 'flat',
                'qty' => (int) ($row['qty'] ?? 0),
                'sku' => $val('sku'),
                'description' => $val('description'),
                'meta_title' => $val('meta_title'),
                'meta_description' => $val('meta_description'),
                'payment_policy' => $val('payment_policy') ?? 'full_cod',
                'partial_payment_type' => $val('partial_payment_type') ?? 'flat',
                'partial_advance_amount' => (float) ($row['partial_advance_amount'] ?? 0),
                'published' => ($row['published'] ?? '1') !== '0',
                'image_url' => $val('thumbnail_url'),
            ];

            if ($download && ! empty($data['image_url'])) {
                $data['thumbnail_upload_id'] = $this->downloadImage($data['image_url'], $request->user()->id);
            }

            $this->products->save(new Product, $data, $request->user()->id);
            $created++;
        }

        return back()
            ->with($created ? 'success' : 'warning', __(':count products imported', ['count' => $created]))
            ->with('import_errors', $errors);
    }

    public function export(Request $request): StreamedResponse
    {
        $query = Product::query()->with(['categories:id', 'stocks'])->orderBy('id');

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['id', ...self::COLUMNS, 'variants']);
            $query->chunk(200, function ($products) use ($out) {
                foreach ($products as $p) {
                    fputcsv($out, [
                        $p->id, $p->name, $p->categories->pluck('id')->implode('|'), $p->brand_id, $p->unit, $p->weight, $p->min_qty,
                        $p->tags, $p->barcode, $p->unit_price, $p->sale_price, $p->discount, $p->discount_type, $p->current_stock, $p->sku,
                        $p->description, $p->meta_title, $p->meta_description,
                        $p->payment_policy, $p->partial_payment_type, $p->partial_advance_amount, $p->thumbnailUrl(), $p->published ? 1 : 0,
                        $p->variant_product ? $p->stocks->map(fn ($s) => "{$s->variant}:{$s->price}:{$s->qty}")->implode(' | ') : '',
                    ]);
                }
            });
            fclose($out);
        }, 'products_'.now()->format('Y-m-d_His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array{0: array<string>, 1: array<array<string>>} */
    private function readCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle) ?: [];
        if ($header) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        }
        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $header);
        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (array_filter($row, fn ($v) => trim((string) $v) !== '')) {
                $rows[] = $row;
            }
        }
        fclose($handle);

        return [$header, $rows];
    }

    private function downloadImage(string $url, int $userId): ?int
    {
        try {
            $response = Http::timeout(20)->get($url);
            if (! $response->successful()) {
                return null;
            }
            $tmp = tempnam(sys_get_temp_dir(), 'img');
            file_put_contents($tmp, $response->body());
            $name = basename(parse_url($url, PHP_URL_PATH) ?: 'image.jpg');

            return $this->uploads->store(new UploadedFile($tmp, $name, null, null, true), $userId)->id;
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    private function csv(string $filename, array $header, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $header);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
