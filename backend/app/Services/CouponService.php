<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/** Checks a coupon against a cart and works out the discount. */
class CouponService
{
    /**
     * @param  array<array{product_id:int, total:float}>  $lines  priced cart lines
     * @return array{coupon: Coupon, discount: float}
     */
    public function apply(string $code, array $lines, ?string $phone = null): array
    {
        $fail = fn (string $msg) => throw ValidationException::withMessages(['coupon_code' => $msg]);
        if (! setting_on('coupon_system', true)) {
            $fail(__('Coupons are not available right now.'));
        }

        $coupon = Coupon::query()->where('code', strtoupper(trim($code)))->where('status', true)->first();
        if (! $coupon) {
            $fail(__('This coupon code is not valid.'));
        }
        if (($coupon->start_date && $coupon->start_date > now()) || ($coupon->end_date && $coupon->end_date < now())) {
            $fail(__('This coupon has expired.'));
        }
        if ($coupon->usage_limit && $coupon->usages()->count() >= $coupon->usage_limit) {
            $fail(__('This coupon has been fully used.'));
        }
        if ($phone && $coupon->usage_limit_per_user && $coupon->usages()->where('phone', $phone)->count() >= $coupon->usage_limit_per_user) {
            $fail(__('You have already used this coupon.'));
        }
        if ($coupon->user_ids) {
            $allowed = $phone && User::query()->whereIn('id', $coupon->user_ids)->where('phone', $phone)->exists();
            if (! $allowed) {
                $fail(__('This coupon is not available for your number.'));
            }
        }

        $subtotal = array_sum(array_column($lines, 'total'));
        $base = $coupon->type === 'product'
            ? array_sum(array_map(fn ($l) => in_array((int) $l['product_id'], array_map('intval', $coupon->product_ids ?? []), true) ? $l['total'] : 0, $lines))
            : $subtotal;

        if ($coupon->type === 'product' && $base <= 0) {
            $fail(__('This coupon does not apply to the products in your cart.'));
        }
        if ($coupon->type === 'cart' && $subtotal < (float) $coupon->min_buy) {
            $fail(__('Minimum shopping of :amount needed for this coupon.', ['amount' => format_price($coupon->min_buy)]));
        }

        $discount = $coupon->discount_type === 'percent' ? $base * (float) $coupon->discount / 100 : (float) $coupon->discount;
        if ($coupon->max_discount !== null && (float) $coupon->max_discount > 0) {
            $discount = min($discount, (float) $coupon->max_discount);
        }

        return ['coupon' => $coupon, 'discount' => round(min($discount, $base), 2)];
    }

    public function recordUse(Coupon $coupon, int $orderId, ?int $userId, string $phone, float $amount): void
    {
        CouponUsage::query()->create(['coupon_id' => $coupon->id, 'order_id' => $orderId, 'user_id' => $userId, 'phone' => $phone, 'amount' => $amount, 'created_at' => now()]);
    }
}
