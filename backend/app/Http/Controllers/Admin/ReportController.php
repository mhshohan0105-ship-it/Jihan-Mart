<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\ReportService;
use App\Support\DateRange;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Sales reports; every report can be exported (?export=csv) and printed. */
class ReportController extends Controller
{
    public function __construct(private ReportService $reports) {}

    public function dateWise(Request $request): View|StreamedResponse
    {
        $range = DateRange::fromRequest($request, 'this_month');
        $status = $request->input('delivery_status', 'delivered');
        $group = $request->input('group_by', 'invoice');
        $lines = $this->reports->lines($range, $status, $request->input('payment_status'), $request->integer('category_id') ?: null, $request->input('search'));

        $totals = [
            'amount' => (float) (clone $lines)->sum('total'),
            'pieces' => (int) (clone $lines)->sum('quantity'),
            'orders' => (clone $lines)->distinct()->count('order_id'),
            'products' => (clone $lines)->distinct()->count('product_id'),
        ];

        if ($group === 'invoice') {
            $rows = $lines->orderByDesc('order_id');
        } else {
            // aggregated: by date + product, or by product for the whole period
            $all = $lines->get();
            $key = $group === 'date_product' ? fn ($d) => $d->order->created_at->format('Y-m-d').'|'.$d->product_id.'|'.$d->variant : fn ($d) => $d->product_id.'|'.$d->variant;
            $rows = $all->groupBy($key)->map(fn ($g) => tap(clone $g->first(), function ($d) use ($g) {
                $d->quantity = $g->sum('quantity');
                $d->total = $g->sum('total');
                $d->price = $d->quantity ? round($d->total / $d->quantity, 2) : 0;
            }))->values();
        }

        if ($request->input('export') === 'csv') {
            $data = $group === 'invoice' ? $rows->get() : $rows;

            return $this->csv('date_wise_sales.csv', ['Date', 'Invoice', 'Product', 'Variation', 'Category', 'Pieces', 'Avg Unit Price', 'Total Sales', 'Payment'],
                $data->map(fn ($d) => [
                    format_date($d->order->created_at, false), $group === 'invoice' ? $d->order->code : '', $d->product_name, $d->variant,
                    $d->product?->categories->pluck('name')->implode(', '), $d->quantity, $d->price, $d->total, $d->order->payment_status,
                ]));
        }

        return view('admin.reports.date-wise', [
            'range' => $range, 'status' => $status, 'group' => $group, 'totals' => $totals,
            'rows' => $group === 'invoice' ? $rows->paginate(20)->withQueryString() : $rows,
            'categories' => Category::tree(),
        ]);
    }

    public function summary(Request $request): View|StreamedResponse
    {
        $range = DateRange::fromRequest($request, 'this_month');
        $status = $request->input('delivery_status', 'delivered');
        $mode = $request->input('view_mode', 'daily');
        $rows = $this->reports->summary($range, $status, $request->input('payment_status'), $mode);

        if ($request->input('export') === 'csv') {
            return $this->csv('sale_summary.csv', ['Date', 'Orders', 'Invoices', 'Pieces', 'Product Sales', 'Shipping', 'Discount', 'Grand Total', 'AOV', 'Top Selling Product'],
                $rows->map(fn ($r) => [
                    $mode === 'monthly' ? $r['date']->format('M Y') : $r['date']->format('d-m-Y'), $r['orders'], $r['codes']->pluck('code')->implode(' '),
                    $r['pieces'], $r['product_sales'], $r['shipping'], $r['discount'], $r['total'], $r['aov'], $r['top'],
                ]));
        }

        return view('admin.reports.summary', [
            'range' => $range, 'status' => $status, 'mode' => $mode, 'rows' => $rows,
            'totals' => [
                'revenue' => $rows->sum('total'), 'orders' => $rows->sum('orders'), 'pieces' => $rows->sum('pieces'),
                'aov' => $rows->sum('orders') ? round($rows->sum('total') / $rows->sum('orders'), 2) : 0,
            ],
        ]);
    }

    public function productSale(Request $request): View|StreamedResponse
    {
        $q = Product::query()->with('thumbnail')->orderByDesc('num_of_sale');
        $this->filterCategory($q, $request);
        if ($request->input('export') === 'csv') {
            return $this->csv('product_sale.csv', ['Product', 'SKU', 'Num of Sale'], $q->get()->map(fn ($p) => [$p->name, $p->sku, $p->num_of_sale]));
        }

        return view('admin.reports.products', ['products' => $q->paginate(20)->withQueryString(), 'categories' => Category::tree(), 'mode' => 'sale']);
    }

    public function stock(Request $request): View|StreamedResponse
    {
        $q = Product::query()->with(['thumbnail', 'stocks'])->orderBy('current_stock');
        $this->filterCategory($q, $request);
        if ($request->input('export') === 'csv') {
            return $this->csv('product_stock.csv', ['Product', 'SKU', 'Stock'], $q->get()->map(fn ($p) => [$p->name, $p->sku, $p->current_stock]));
        }

        return view('admin.reports.products', ['products' => $q->paginate(20)->withQueryString(), 'categories' => Category::tree(), 'mode' => 'stock']);
    }

    private function filterCategory($q, Request $request): void
    {
        if ($id = $request->integer('category_id')) {
            $ids = Category::query()->find($id)?->descendantIds() ?? [$id];
            $q->whereHas('categories', fn ($c) => $c->whereIn('categories.id', $ids));
        }
    }

    private function csv(string $name, array $header, iterable $rows): StreamedResponse
    {
        abort_unless(auth()->user()->can('export_reports'), 403);

        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $header);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, now()->format('Y-m-d_').$name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
