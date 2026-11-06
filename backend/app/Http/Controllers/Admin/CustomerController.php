<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $customers = User::query()->where('type', User::TYPE_CUSTOMER)
            ->withCount('orders')->withSum('orders', 'grand_total')
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', '%'.$request->search.'%')
                ->orWhere('email', 'like', '%'.$request->search.'%')
                ->orWhere('phone', 'like', '%'.(cleanBdPhone($request->search) ?? $request->search).'%')))
            ->latest('id')->paginate(15)->withQueryString();

        return view('admin.customers.index', compact('customers'));
    }

    public function show(User $customer): View
    {
        abort_unless($customer->type === User::TYPE_CUSTOMER, 404);

        return view('admin.customers.show', [
            'customer' => $customer,
            'orders' => Order::query()->where('user_id', $customer->id)->latest('id')->paginate(15),
            'stats' => Order::query()->where('user_id', $customer->id)->selectRaw('delivery_status, count(*) as c')->groupBy('delivery_status')->pluck('c', 'delivery_status'),
        ]);
    }

    public function ban(Request $request, User $customer): JsonResponse
    {
        abort_unless($customer->type === User::TYPE_CUSTOMER, 404);
        $customer->update(['banned' => $request->boolean('value')]);

        return response()->json(['success' => true, 'message' => $customer->banned ? __('Customer banned') : __('Customer unbanned')]);
    }

    public function destroy(User $customer): JsonResponse
    {
        abort_unless($customer->type === User::TYPE_CUSTOMER, 404);
        $customer->delete();

        return response()->json(['success' => true, 'message' => __('Customer deleted')]);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $count = User::query()->where('type', User::TYPE_CUSTOMER)->whereIn('id', array_map('intval', (array) $request->input('ids', [])))->delete();

        return response()->json(['success' => true, 'message' => __(':count customers deleted', ['count' => $count])]);
    }
}
