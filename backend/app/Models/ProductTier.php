<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** Quantity based price: buying min_qty or more costs `price` per unit. */
#[Fillable(['product_id', 'min_qty', 'price'])]
class ProductTier extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return ['price' => 'decimal:2'];
    }
}
