<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'tax_id', 'tax', 'tax_type'])]
class ProductTax extends Model
{
    public $timestamps = false;

    public function taxType(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'tax_id');
    }

    protected static function booted(): void
    {
        static::saved(fn () => Tax::forgetMap());
        static::deleted(fn () => Tax::forgetMap());
    }
}
