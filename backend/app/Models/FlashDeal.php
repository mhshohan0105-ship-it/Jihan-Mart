<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'slug', 'background_color', 'text_color', 'banner_upload_id', 'start_date', 'end_date', 'status', 'featured'])]
class FlashDeal extends Model
{
    use HasUniqueSlug;

    protected function casts(): array
    {
        return ['start_date' => 'datetime', 'end_date' => 'datetime', 'status' => 'boolean', 'featured' => 'boolean'];
    }

    /** HasUniqueSlug reads `name`; flash deals use `title`. */
    public function getNameAttribute(): ?string
    {
        return $this->title;
    }

    public function items(): HasMany
    {
        return $this->hasMany(FlashDealProduct::class);
    }

    public function scopeRunning(Builder $q): Builder
    {
        return $q->where('status', true)->where('start_date', '<=', now())->where('end_date', '>=', now());
    }

    public function isRunning(): bool
    {
        return $this->status && $this->start_date <= now() && $this->end_date >= now();
    }

    /**
     * product id => [discount, discount_type] for every running deal (first deal wins). One query per request.
     *
     * @return array<int, array{0: float, 1: string}>
     */
    public static function priceMap(): array
    {
        return app()->bound('flash.map') ? app('flash.map') : tap(self::buildMap(), fn ($map) => app()->instance('flash.map', $map));
    }

    public static function forgetMap(): void
    {
        app()->forgetInstance('flash.map');
    }

    private static function buildMap(): array
    {
        $map = [];
        $rows = FlashDealProduct::query()->whereIn('flash_deal_id', self::query()->running()->select('id'))->orderBy('id')->get();
        foreach ($rows as $row) {
            $map[$row->product_id] ??= [(float) $row->discount, $row->discount_type];
        }

        return $map;
    }

    protected static function booted(): void
    {
        static::saved(fn () => self::forgetMap());
        static::deleted(fn () => self::forgetMap());
    }
}
