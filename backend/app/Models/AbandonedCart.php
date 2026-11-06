<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Checkout details captured as soon as a phone number is typed, before the order is placed. */
#[Fillable(['session_key', 'name', 'phone', 'address', 'state_id', 'city_id', 'items', 'source', 'status', 'order_id'])]
class AbandonedCart extends Model
{
    protected function casts(): array
    {
        return ['items' => 'array'];
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
