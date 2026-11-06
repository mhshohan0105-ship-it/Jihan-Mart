<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Fillable(['type', 'value', 'lang'])]
class BusinessSetting extends Model
{
    public const CACHE_KEY = 'business_settings';

    /** All settings as [type => value] (non translatable) plus [type.lang => value]. */
    public static function allCached(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $out = [];
            foreach (self::query()->get(['type', 'value', 'lang']) as $row) {
                $out[$row->lang ? $row->type.'.'.$row->lang : $row->type] = $row->value;
            }

            return $out;
        });
    }

    public static function put(string $type, mixed $value, ?string $lang = null): void
    {
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        self::query()->updateOrCreate(['type' => $type, 'lang' => $lang], ['value' => $value]);
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }
}
