<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['type', 'code', 'discount', 'discount_type', 'min_buy', 'max_discount', 'product_ids', 'user_ids',
    'usage_limit', 'usage_limit_per_user', 'start_date', 'end_date', 'status'])]
class Coupon extends Model
{
    public const TYPES = ['cart' => 'For Total Orders', 'product' => 'For Products'];

    protected function casts(): array
    {
        return [
            'discount' => 'decimal:2', 'min_buy' => 'decimal:2', 'max_discount' => 'decimal:2',
            'product_ids' => 'array', 'user_ids' => 'array',
            'start_date' => 'datetime', 'end_date' => 'datetime', 'status' => 'boolean',
        ];
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    protected function setCodeAttribute(string $value): void
    {
        $this->attributes['code'] = strtoupper(trim($value));
    }
}
