<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Area / thana with its own delivery charge. */
#[Fillable(['state_id', 'name', 'cost', 'status', 'pathao_city_id', 'pathao_zone_id', 'pathao_area_id'])]
class City extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return ['cost' => 'decimal:2', 'status' => 'boolean'];
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }
}
