<?php

namespace App\Services;

use App\Models\City;

/** Delivery charge for a cart, following Setup → Shipping Configuration. */
class ShippingService
{
    /**
     * @param  bool  $allFreeShipping  every product in the cart has "Free Shipping" on
     */
    public function cost(?int $cityId, float $subtotal, bool $allFreeShipping = false): float
    {
        if ($allFreeShipping) {
            return 0.0;
        }
        if (setting_on('free_shipping_enabled') && $subtotal >= (float) get_setting('free_shipping_min_amount', 0) && (float) get_setting('free_shipping_min_amount', 0) > 0) {
            return 0.0;
        }

        return match (get_setting('shipping_type', 'area_wise')) {
            'flat_rate' => (float) get_setting('flat_rate_shipping_cost', 0),
            default => (float) (City::query()->find($cityId)?->cost ?? get_setting('flat_rate_shipping_cost', 0)),
        };
    }
}
