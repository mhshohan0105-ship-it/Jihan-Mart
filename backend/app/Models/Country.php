<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'code', 'status'])]
class Country extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return ['status' => 'boolean'];
    }

    public function states(): HasMany
    {
        return $this->hasMany(State::class);
    }
}
