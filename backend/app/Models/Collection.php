<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Promotional product group ("Free Delivery", "Up To 50% Off", "Mystery Box"...). */
#[Fillable(['name', 'slug', 'start_date', 'end_date', 'image_upload_id', 'status'])]
class Collection extends Model
{
    use HasUniqueSlug;

    protected function casts(): array
    {
        return ['start_date' => 'datetime', 'end_date' => 'datetime', 'status' => 'boolean'];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    /** Switched on and inside its date window (open ended when a date is empty). */
    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', true)
            ->where(fn ($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', now()))
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()));
    }
}
