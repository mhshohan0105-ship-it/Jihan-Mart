<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'logo_upload_id', 'meta_title', 'meta_description'])]
class Brand extends Model
{
    use HasUniqueSlug;

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
