<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One row per variant ('' for a simple product). Stock is always counted here. */
#[Fillable(['product_id', 'variant', 'sku', 'price', 'qty', 'image_upload_id'])]
class ProductStock extends Model
{
    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'qty' => 'integer'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
