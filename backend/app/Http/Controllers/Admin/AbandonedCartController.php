<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AbandonedCart;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AbandonedCartController extends Controller
{
    public function index(Request $request): View
    {
        $carts = AbandonedCart::query()->with(['state', 'city'])
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', '%'.$request->search.'%')
                ->orWhere('phone', 'like', '%'.(cleanBdPhone($request->search) ?? $request->search).'%')
                ->orWhere('address', 'like', '%'.$request->search.'%')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest('updated_at')->paginate(15)->withQueryString();

        return view('admin.orders.abandoned', compact('carts'));
    }

    /** Turn a captured cart into a real pending order. */
    public function convert(Request $request, AbandonedCart $cart, OrderService $orders): JsonResponse
    {
        abort_if($cart->status === 'converted', 422, __('Already converted'));

        $order = $orders->create([
            'name' => $cart->name ?: __('Customer'),
            'phone' => $cart->phone,
            'address' => $cart->address ?: '—',
            'state_id' => $cart->state_id,
            'city_id' => $cart->city_id,
            'items' => $cart->items,
        ], 'abandoned', $request->user());
        $cart->update(['status' => 'converted', 'order_id' => $order->id]);

        return response()->json(['success' => true, 'message' => __('Order #:code created', ['code' => $order->code])]);
    }

    public function destroy(AbandonedCart $cart): JsonResponse
    {
        $cart->delete();

        return response()->json(['success' => true, 'message' => __('Deleted successfully')]);
    }
}
