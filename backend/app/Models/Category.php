<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[Fillable(['parent_id', 'level', 'name', 'name_bn', 'slug', 'order_level', 'banner_upload_id', 'image_upload_id', 'icon',
    'featured', 'single_grid', 'megamenu', 'mega_section', 'meta_title', 'meta_description'])]
class Category extends Model
{
    use HasUniqueSlug;

    protected function casts(): array
    {
        return ['featured' => 'boolean', 'single_grid' => 'boolean', 'megamenu' => 'boolean'];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderByDesc('order_level')->orderBy('name');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    /** All categories as a flat list in tree order, each with a `depth` attribute (for "‑‑" indented selects). */
    public static function tree(): Collection
    {
        $all = self::query()->orderByDesc('order_level')->orderBy('name')->get()->groupBy('parent_id');
        $out = collect();
        $walk = function ($parentId, $depth) use (&$walk, $all, $out) {
            foreach ($all->get($parentId ?? '', collect()) as $cat) {
                $cat->depth = $depth;
                $out->push($cat);
                $walk($cat->id, $depth + 1);
            }
        };
        $walk(null, 0);

        return $out;
    }

    /** Ids of this category and every category below it. */
    public function descendantIds(): array
    {
        $ids = [$this->id];
        foreach ($this->children()->get() as $child) {
            $ids = [...$ids, ...$child->descendantIds()];
        }

        return $ids;
    }

    protected static function booted(): void
    {
        static::saving(function (Category $category) {
            $category->level = $category->parent_id ? (int) (self::query()->find($category->parent_id)?->level) + 1 : 0;
        });
    }
}
