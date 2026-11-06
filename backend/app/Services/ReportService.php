<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Support\DateRange;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/** Sales numbers for the dashboard and reports. Revenue never counts cancelled or returned orders. */
class ReportService
{
    public const LOST = ['cancelled', 'returned'];

    public function orders(DateRange $range, ?string $deliveryStatus = null, ?string $paymentStatus = null): Builder
    {
        $q = $range->apply(Order::query());
        if ($deliveryStatus === 'all' || $deliveryStatus === null) {
            // all statuses
        } elseif ($deliveryStatus === 'sales') {
            $q->whereNotIn('delivery_status', self::LOST);
        } else {
            $q->where('delivery_status', $deliveryStatus);
        }
        if ($paymentStatus === 'paid') {
            $q->where('payment_status', 'paid');
        } elseif ($paymentStatus === 'unpaid') {
            $q->where('payment_status', '!=', 'paid');
        }

        return $q;
    }

    public function dashboard(DateRange $range): array
    {
        $sales = fn (Builder $q) => $q->whereNotIn('delivery_status', self::LOST);
        $today = Order::query()->whereDate('created_at', today());
        $month = Order::query()->where('created_at', '>=', now()->startOfMonth());

        $statusRows = $range->apply(Order::query())->selectRaw('delivery_status, count(*) as c, sum(grand_total) as amount')->groupBy('delivery_status')->get()->keyBy('delivery_status');
        $totalInRange = (int) $statusRows->sum('c');

        return [
            'revenue' => (float) $sales($range->apply(Order::query()))->sum('grand_total'),
            'today_revenue' => (float) $sales(clone $today)->sum('grand_total'),
            'today_orders' => (clone $today)->count(),
            'month_revenue' => (float) $sales(clone $month)->sum('grand_total'),
            'orders' => $totalInRange,
            'statuses' => collect(Order::DELIVERY_STATUSES)->map(fn ($label, $key) => [
                'label' => $label,
                'count' => (int) ($statusRows[$key]->c ?? 0),
                'amount' => (float) ($statusRows[$key]->amount ?? 0),
                'percent' => $totalInRange ? round(($statusRows[$key]->c ?? 0) / $totalInRange * 100, 1) : 0,
            ]),
        ];
    }

    /** Revenue per month of the given year (12 values). */
    public function monthly(int $year): array
    {
        $rows = Order::query()->whereYear('created_at', $year)->whereNotIn('delivery_status', self::LOST)->get(['created_at', 'grand_total'])
            ->groupBy(fn ($o) => (int) $o->created_at->format('n'))->map->sum('grand_total');

        return array_map(fn ($m) => round((float) ($rows[$m] ?? 0), 2), range(1, 12));
    }

    /** [labels, values] for the last N days. */
    public function daily(int $days = 7): array
    {
        $from = now()->subDays($days - 1)->startOfDay();
        $rows = Order::query()->where('created_at', '>=', $from)->whereNotIn('delivery_status', self::LOST)->get(['created_at', 'grand_total'])
            ->groupBy(fn ($o) => $o->created_at->format('Y-m-d'))->map->sum('grand_total');
        $labels = [];
        $values = [];
        for ($d = $from->copy(); $d <= now(); $d->addDay()) {
            $labels[] = $d->format('d M');
            $values[] = round((float) ($rows[$d->format('Y-m-d')] ?? 0), 2);
        }

        return [$labels, $values];
    }

    /** Sales amount per top level category in the range. */
    public function categorySales(DateRange $range): Collection
    {
        $orderIds = $this->orders($range, 'sales')->pluck('id');
        $byProduct = OrderDetail::query()->whereIn('order_id', $orderIds)->selectRaw('product_id, sum(total) as amount')->groupBy('product_id')->pluck('amount', 'product_id');
        $topOf = $this->topCategoryMap();
        $productCats = Product::query()->whereIn('id', $byProduct->keys())->with('categories:id')->get()->mapWithKeys(fn ($p) => [$p->id => $topOf[$p->categories->first()?->id] ?? null]);

        $names = Category::query()->whereNull('parent_id')->pluck('name', 'id');

        return $byProduct->groupBy(fn ($amount, $pid) => $productCats[$pid] ?? 0, true)
            ->map(fn ($g, $cat) => ['name' => $names[$cat] ?? __('Other'), 'amount' => round((float) $g->sum(), 2)])
            ->sortByDesc('amount')->values();
    }

    /** Units in stock per top level category. */
    public function categoryStock(): Collection
    {
        $topOf = $this->topCategoryMap();
        $names = Category::query()->whereNull('parent_id')->pluck('name', 'id');

        return Product::query()->with('categories:id')->get(['id', 'current_stock'])
            ->groupBy(fn ($p) => $topOf[$p->categories->first()?->id] ?? 0)
            ->map(fn ($g, $cat) => ['name' => $names[$cat] ?? __('Other'), 'stock' => (int) $g->sum('current_stock')])
            ->sortByDesc('stock')->values();
    }

    public function lowStock(int $limit = 10)
    {
        return Product::query()->with('thumbnail')->where('current_stock', '<=', (int) get_setting('low_stock_threshold', 5))
            ->orderBy('current_stock')->paginate($limit, ['*'], 'low_page');
    }

    public function topProducts(int $limit = 8): Collection
    {
        return Product::query()->with('thumbnail')->where('num_of_sale', '>', 0)->orderByDesc('num_of_sale')->limit($limit)->get();
    }

    /** Order lines for the date wise report. */
    public function lines(DateRange $range, string $status, ?string $payment, ?int $categoryId, ?string $search): Builder
    {
        $orderQuery = $this->orders($range, $status === 'delivered_default' ? 'delivered' : $status, $payment);
        $q = OrderDetail::query()->whereIn('order_id', $orderQuery->select('id'))->with(['order:id,code,created_at,payment_status', 'product.thumbnail', 'product.categories:id,name']);
        if ($categoryId) {
            $ids = Category::query()->find($categoryId)?->descendantIds() ?? [$categoryId];
            $q->whereHas('product.categories', fn ($c) => $c->whereIn('categories.id', $ids));
        }
        if ($search = trim((string) $search)) {
            $q->where(fn ($q) => $q->where('product_name', 'like', "%{$search}%")->orWhereHas('order', fn ($o) => $o->where('code', ltrim($search, '#'))));
        }

        return $q;
    }

    /** Day by day (or month by month) breakdown for the sale summary. */
    public function summary(DateRange $range, string $status, ?string $payment, string $mode): Collection
    {
        $orders = $this->orders($range, $status, $payment)->with('details')->orderBy('created_at')->get();
        $format = $mode === 'monthly' ? 'Y-m' : 'Y-m-d';

        return $orders->groupBy(fn ($o) => $o->created_at->format($format))->map(function ($group, $key) use ($mode) {
            $details = $group->flatMap->details;
            $top = $details->groupBy('product_name')->map->sum('quantity')->sortDesc();
            $date = $mode === 'monthly' ? Carbon::createFromFormat('Y-m', $key)->startOfMonth() : Carbon::parse($key);

            return [
                'date' => $date,
                'orders' => $group->count(),
                'codes' => $group->map(fn ($o) => ['code' => $o->code, 'id' => $o->encryptedId()])->values(),
                'pieces' => (int) $details->sum('quantity'),
                'product_sales' => (float) $group->sum('subtotal'),
                'shipping' => (float) $group->sum('shipping_cost'),
                'discount' => (float) $group->sum('discount') + (float) $group->sum('coupon_discount'),
                'total' => (float) $group->sum('grand_total'),
                'aov' => $group->count() ? round((float) $group->sum('grand_total') / $group->count(), 2) : 0,
                'top' => $top->keys()->first() ? $top->keys()->first().' ('.$top->first().')' : '—',
            ];
        })->values();
    }

    /** category id (any level) => top level category id */
    private function topCategoryMap(): array
    {
        $all = Category::query()->get(['id', 'parent_id'])->keyBy('id');
        $map = [];
        foreach ($all as $id => $cat) {
            $top = $cat;
            while ($top->parent_id && isset($all[$top->parent_id])) {
                $top = $all[$top->parent_id];
            }
            $map[$id] = $top->id;
        }

        return $map;
    }
}
