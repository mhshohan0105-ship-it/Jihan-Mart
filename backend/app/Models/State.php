<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Delivery zone / district. */
#[Fillable(['country_id', 'name', 'status', 'pathao_city_id'])]
class State extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return ['status' => 'boolean'];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class)->orderBy('name');
    }
}
