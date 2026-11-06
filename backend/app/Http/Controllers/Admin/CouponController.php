<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function index(): View
    {
        return view('admin.marketing.coupon-index', ['coupons' => Coupon::query()->withCount('usages')->withSum('usages', 'amount')->latest('id')->paginate(15)]);
    }

    public function create(): View
    {
        return view('admin.marketing.coupon-form', $this->formData(new Coupon(['type' => 'cart', 'discount_type' => 'flat', 'status' => true])));
    }

    public function store(Request $request): RedirectResponse
    {
        Coupon::query()->create($this->validated($request));

        return redirect()->route('admin.coupons.index')->with('success', __('Coupon has been saved successfully'));
    }

    public function edit(Coupon $coupon): View
    {
        return view('admin.marketing.coupon-form', $this->formData($coupon));
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $coupon->update($this->validated($request, $coupon));

        return redirect()->route('admin.coupons.index')->with('success', __('Coupon has been saved successfully'));
    }

    public function destroy(Coupon $coupon): JsonResponse
    {
        $coupon->delete();

        return response()->json(['success' => true, 'message' => __('Coupon has been deleted successfully')]);
    }

    private function validated(Request $request, ?Coupon $coupon = null): array
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(Coupon::TYPES))],
            'code' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9_]+$/', Rule::unique('coupons', 'code')->ignore($coupon?->id)],
            'discount' => ['required', 'numeric', 'min:0'],
            'discount_type' => ['required', 'in:flat,percent'],
            'min_buy' => ['nullable', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'product_ids' => ['nullable', 'array', 'required_if:type,product'],
            'product_ids.*' => ['integer'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_user' => ['nullable', 'integer', 'min:1'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);
        $data['code'] = strtoupper($data['code']);
        $data['min_buy'] = (float) ($data['min_buy'] ?? 0);
        $data['product_ids'] = $data['type'] === 'product' ? array_map('intval', $data['product_ids'] ?? []) : null;
        $data['user_ids'] = ! empty($data['user_ids']) ? array_map('intval', $data['user_ids']) : null;
        $data['status'] = $request->boolean('status');

        return $data;
    }

    private function formData(Coupon $coupon): array
    {
        return [
            'coupon' => $coupon,
            'products' => Product::query()->orderBy('name')->get(['id', 'name']),
            'customers' => User::query()->where('type', User::TYPE_CUSTOMER)->latest('id')->limit(500)->get(['id', 'name', 'phone']),
        ];
    }
}
