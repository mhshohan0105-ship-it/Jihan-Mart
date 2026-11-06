<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Color;
use App\Models\Product;
use App\Models\Tax;
use App\Services\ProductService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(private ProductService $products) {}

    public function index(Request $request): View
    {
        return view('admin.products.index', [
            'products' => $this->filtered($request)->paginate(15)->withQueryString(),
            'collections' => Collection::query()->orderBy('id')->get(['id', 'name']),
            'lowStock' => (int) get_setting('low_stock_threshold', 5),
        ]);
    }

    public function create(): View
    {
        return view('admin.products.form', $this->formData(new Product([
            'min_qty' => 1, 'published' => true, 'direct_order' => true, 'discount_type' => 'flat',
            'payment_policy' => 'full_cod', 'partial_payment_type' => 'flat',
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $product = $this->products->save(new Product, $this->validated($request), $request->user()->id);

        return redirect()->route('admin.products.edit', $product)->with('success', __('Product has been inserted successfully'));
    }

    public function edit(Product $product): View
    {
        return view('admin.products.form', $this->formData($product->load(['categories', 'stocks', 'tiers'])));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->products->save($product, $this->validated($request, $product));

        return back()->with('success', __('Product has been updated successfully'));
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(['success' => true, 'message' => __('Product has been deleted successfully')]);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $ids = array_map('intval', (array) $request->input('ids', []));
        $count = Product::query()->whereIn('id', $ids)->delete();

        return response()->json(['success' => true, 'message' => __(':count products deleted', ['count' => $count])]);
    }

    public function duplicate(Request $request, Product $product): RedirectResponse
    {
        $copy = $this->products->duplicate($product, $request->user()->id);

        return redirect()->route('admin.products.edit', $copy)->with('success', __('Product has been duplicated successfully'));
    }

    /** Variant names for the chosen colours/attribute values (builds the variant table in the form). */
    public function combinations(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->products->combinations(
                array_map('intval', (array) $request->input('colors', [])),
                (array) $request->input('choice_options', []),
            ),
        ]);
    }

    private function filtered(Request $request): Builder
    {
        $q = Product::query()->with(['thumbnail', 'stocks', 'collections:id']);

        if ($search = trim((string) $request->input('search'))) {
            $q->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('barcode', $search)
                ->orWhereHas('stocks', fn ($s) => $s->where('sku', 'like', "%{$search}%")));
        }
        match ($request->input('status')) {
            'published', 'approved' => $q->where('published', true),
            'unpublished' => $q->where('published', false),
            default => null,
        };
        match ($request->input('variant')) {
            'simple' => $q->where('variant_product', false),
            'variable' => $q->where('variant_product', true),
            default => null,
        };
        match ($request->input('flag')) {
            'landing_page' => $q->where('landing_page', true),
            'featured' => $q->where('featured', true),
            'todays_deal' => $q->where('todays_deal', true),
            default => null,
        };
        if ($category = $request->integer('category')) {
            $ids = Category::query()->find($category)?->descendantIds() ?? [$category];
            $q->whereHas('categories', fn ($c) => $c->whereIn('categories.id', $ids));
        }

        return match ($request->input('sort')) {
            'sale_desc' => $q->orderByDesc('num_of_sale'),
            'sale_asc' => $q->orderBy('num_of_sale'),
            'stock_desc' => $q->orderByDesc('current_stock'),
            'stock_asc' => $q->orderBy('current_stock'),
            'price_desc' => $q->orderByDesc('unit_price'),
            'price_asc' => $q->orderBy('unit_price'),
            default => $q->latest('id'),
        };
    }

    private function formData(Product $product): array
    {
        return [
            'product' => $product,
            'tree' => Category::tree(),
            'brands' => Brand::query()->orderBy('name')->get(['id', 'name']),
            'colors' => Color::query()->orderBy('name')->get(['id', 'name', 'code']),
            'attributes' => Attribute::query()->with('values')->orderBy('name')->get(),
            'collections' => Collection::query()->orderBy('name')->get(['id', 'name']),
            'taxes' => Tax::query()->where('status', true)->orderBy('id')->get(['id', 'name']),
            'productTaxes' => $product->exists ? $product->taxes()->get()->keyBy('tax_id') : collect(),
        ];
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['integer', Rule::exists('categories', 'id')],
            'brand_id' => ['nullable', 'integer', Rule::exists('brands', 'id')],
            'unit' => ['nullable', 'string', 'max:30'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'min_qty' => ['required', 'integer', 'min:1'],
            'tags' => ['nullable', 'string', 'max:1000'],
            'suggestion_category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'barcode' => ['nullable', 'string', 'max:40', Rule::unique('products', 'barcode')->ignore($product?->id)],
            'thumbnail_upload_id' => ['nullable', 'integer'],
            'photos' => ['nullable', 'string', 'max:255'],
            'video_provider' => ['nullable', 'in:youtube,dailymotion,vimeo'],
            'video_link' => ['nullable', 'url', 'max:255'],
            'colors' => ['nullable', 'array'],
            'colors.*' => ['integer', Rule::exists('colors', 'id')],
            'choice_options' => ['nullable', 'array'],
            'choice_options.*.attribute_id' => ['required', 'integer', Rule::exists('attributes', 'id')],
            'choice_options.*.values' => ['nullable', 'array'],
            'choice_options.*.values.*' => ['string', 'max:120'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'discount_start_date' => ['nullable', 'date'],
            'discount_end_date' => ['nullable', 'date', 'after_or_equal:discount_start_date'],
            'discount' => ['required', 'numeric', 'min:0'],
            'discount_type' => ['required', 'in:flat,percent'],
            'qty' => ['nullable', 'integer'],
            'sku' => ['nullable', 'string', 'max:60'],
            'external_link' => ['nullable', 'url', 'max:255'],
            'external_link_btn' => ['nullable', 'string', 'max:60'],
            'stocks' => ['nullable', 'array'],
            'stocks.*.price' => ['nullable', 'numeric', 'min:0'],
            'stocks.*.sku' => ['nullable', 'string', 'max:60'],
            'stocks.*.qty' => ['nullable', 'integer'],
            'stocks.*.image_upload_id' => ['nullable', 'integer'],
            'tiers' => ['nullable', 'array'],
            'taxes' => ['nullable', 'array'],
            'taxes.*.tax' => ['nullable', 'numeric', 'min:0'],
            'taxes.*.tax_type' => ['nullable', 'in:flat,percent'],
            'tiers.*.min_qty' => ['nullable', 'integer', 'min:2'],
            'tiers.*.price' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'pdf_upload_id' => ['nullable', 'integer'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'meta_upload_id' => ['nullable', 'integer'],
            'payment_policy' => ['required', Rule::in(array_keys(Product::PAYMENT_POLICIES))],
            'partial_payment_type' => ['required_if:payment_policy,partial', 'in:flat,percent'],
            'partial_advance_amount' => ['nullable', 'required_if:payment_policy,partial', 'numeric', 'min:0'],
            'shipping_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'warranty' => ['nullable', 'string', 'max:60'],
            'collection_ids' => ['nullable', 'array'],
            'collection_ids.*' => ['integer', Rule::exists('collections', 'id')],
        ]);

        if (($data['discount_type'] ?? '') === 'percent' && (float) $data['discount'] > 100) {
            throw ValidationException::withMessages(['discount' => __('Percent discount cannot be more than 100')]);
        }
        foreach (['featured', 'todays_deal', 'free_shipping', 'direct_order', 'landing_page'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }
        // "Save & Publish" / "Save & Unpublish" buttons
        $data['published'] = $request->input('button') !== 'unpublish';
        $data['partial_advance_amount'] = (float) ($data['partial_advance_amount'] ?? 0);
        $data['collection_ids'] = $data['collection_ids'] ?? [];

        return $data;
    }
}
