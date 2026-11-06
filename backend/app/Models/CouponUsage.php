<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['coupon_id', 'order_id', 'user_id', 'phone', 'amount', 'created_at'])]
class CouponUsage extends Model
{
    public const UPDATED_AT = null;
}
