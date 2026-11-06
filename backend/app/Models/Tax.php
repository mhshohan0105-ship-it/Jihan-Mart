<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'status'])]
class Tax extends Model
{
    protected function casts(): array
    {
        return ['status' => 'boolean'];
    }

    public function products(): HasMany
    {
        return $this->hasMany(ProductTax::class);
    }

    /**
     * product id => [[tax, type], ...] for active taxes. One query per request.
     *
     * @return array<int, array<int, array{0: float, 1: string}>>
     */
    public static function productMap(): array
    {
        if (! app()->bound('tax.map')) {
            $map = [];
            $rows = ProductTax::query()->whereIn('tax_id', self::query()->where('status', true)->select('id'))->where('tax', '>', 0)->get();
            foreach ($rows as $row) {
                $map[$row->product_id][] = [(float) $row->tax, $row->tax_type];
            }
            app()->instance('tax.map', $map);
        }

        return app('tax.map');
    }

    public static function forgetMap(): void
    {
        app()->forgetInstance('tax.map');
    }

    protected static function booted(): void
    {
        static::saved(fn () => self::forgetMap());
        static::deleted(fn () => self::forgetMap());
    }
}
