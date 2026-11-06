<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/** Fills `slug` from `name` on create (and when the name changes), keeping it unique: "lipstick", "lipstick-2". */
trait HasUniqueSlug
{
    protected static function bootHasUniqueSlug(): void
    {
        static::saving(function ($model) {
            if (! $model->slug || ($model->isDirty('name') && ! $model->isDirty('slug'))) {
                $model->slug = static::uniqueSlug($model->name, $model->getKey());
            }
        });
    }

    public static function uniqueSlug(string $name, mixed $ignoreId = null): string
    {
        // Str::slug drops Bangla letters, so fall back to a short random slug for Bangla only names
        $base = Str::slug($name) ?: Str::lower(Str::random(8));
        $slug = $base;
        $i = 2;
        while (static::query()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
