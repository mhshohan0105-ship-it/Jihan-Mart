<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Printable invoices (one or many) and the packaging (picking) checklist. */
class InvoiceController extends Controller
{
    public function invoices(Request $request): View
    {
        $ids = array_map('intval', (array) $request->input('id', []));
        $orders = Order::query()->whereIn('id', $ids)->with(['details.product.thumbnail', 'state', 'city'])->orderBy('code')->get();
        abort_if($orders->isEmpty(), 404);

        return view('admin.orders.invoice', [
            'orders' => $orders,
            'showSku' => setting_on('invoice_show_sku', true),
            'showImage' => setting_on('invoice_show_image', true),
        ]);
    }

    /** Products to pick for confirmed orders, summed per product + variant. */
    public function checklist(Request $request): View
    {
        $query = Order::query()->where('delivery_status', 'confirmed')->with('details.product.thumbnail');
        $requested = array_map('intval', (array) $request->input('id', []));
        if ($requested) {
            $query->whereIn('id', $requested);
        }
        $orders = $query->get();

        $rows = $orders->flatMap(fn (Order $o) => $o->details->map(fn ($d) => ['detail' => $d, 'code' => $o->code]))
            ->groupBy(fn ($r) => $r['detail']->product_id.'|'.$r['detail']->variant)
            ->map(fn ($group) => [
                'detail' => $group->first()['detail'],
                'codes' => $group->pluck('code')->unique()->sort()->values(),
                'qty' => $group->sum(fn ($r) => $r['detail']->quantity),
            ])->sortBy(fn ($r) => $r['detail']->product_name)->values();

        return view('admin.orders.checklist', [
            'rows' => $rows,
            'orderCount' => $orders->count(),
            'skipped' => $requested ? count($requested) - $orders->count() : 0,
        ]);
    }
}
