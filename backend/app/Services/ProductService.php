<?php

namespace App\Services;

use App\Models\Color;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

/** Saves a product with its categories, variant stock rows and quantity tiers in one transaction. */
class ProductService
{
    /**
     * Variant names from the chosen colours and attribute values, e.g. ["Red-S", "Red-M", "Blue-S", "Blue-M"].
     *
     * @param  array<int>  $colorIds
     * @param  array<int, array{attribute_id:int, values:array<string>}>  $choices
     */
    public function combinations(array $colorIds, array $choices): array
    {
        $lists = [];
        if ($colorIds) {
            $names = Color::query()->whereIn('id', $colorIds)->pluck('name', 'id');
            $lists[] = array_values(array_filter(array_map(fn ($id) => $names[$id] ?? null, $colorIds)));
        }
        foreach ($choices as $choice) {
            $values = array_values(array_filter(array_map('trim', $choice['values'] ?? [])));
            if ($values) {
                $lists[] = $values;
            }
        }
        if (! $lists) {
            return [];
        }

        $result = [''];
        foreach ($lists as $list) {
            $next = [];
            foreach ($result as $prefix) {
                foreach ($list as $value) {
                    $next[] = $prefix === '' ? $value : $prefix.'-'.$value;
                }
            }
            $result = $next;
        }

        return $result;
    }

    /**
     * @param  array  $data  validated product fields plus:
     *                       category_ids[], stocks[] (variant => [price, sku, qty, image_upload_id]) or qty/sku for simple,
     *                       tiers[] ([min_qty, price])
     */
    public function save(Product $product, array $data, ?int $userId = null): Product
    {
        return DB::transaction(function () use ($product, $data, $userId) {
            $choices = collect($data['choice_options'] ?? [])
                ->filter(fn ($c) => ! empty($c['attribute_id']) && ! empty($c['values']))
                ->map(fn ($c) => ['attribute_id' => (int) $c['attribute_id'], 'values' => array_values($c['values'])])
                ->values()->all();
            $colors = array_map('intval', $data['colors'] ?? []);
            $variants = $this->combinations($colors, $choices);

            $product->fill(collect($data)->except(['category_ids', 'stocks', 'tiers', 'taxes', 'qty', 'choice_options', 'colors', 'collection_ids'])->all());
            $product->colors = $colors ?: null;
            $product->choice_options = $choices ?: null;
            $product->variant_product = count($variants) > 0;
            if (! $product->exists && $userId) {
                $product->user_id = $userId;
            }
            if (empty($product->barcode)) {
                $product->barcode = $this->newBarcode();
            }
            $product->save();

            $product->categories()->sync($data['category_ids'] ?? []);
            if (array_key_exists('collection_ids', $data)) {
                $product->collections()->sync($data['collection_ids'] ?? []);
            }

            // stock rows: one per variant, or a single '' row for a simple product
            $keep = [];
            if ($variants) {
                foreach ($variants as $variant) {
                    $row = $data['stocks'][$variant] ?? [];
                    $keep[] = $product->stocks()->updateOrCreate(['variant' => $variant], [
                        'price' => $row['price'] ?? $product->unit_price,
                        'sku' => $row['sku'] ?? null,
                        'qty' => (int) ($row['qty'] ?? 0),
                        'image_upload_id' => $row['image_upload_id'] ?? null,
                    ])->id;
                }
            } else {
                $keep[] = $product->stocks()->updateOrCreate(['variant' => ''], [
                    'price' => $product->unit_price,
                    'sku' => $product->sku,
                    'qty' => (int) ($data['qty'] ?? 0),
                ])->id;
            }
            $product->stocks()->whereNotIn('id', $keep)->delete();
            $product->refreshStockTotal();

            $product->tiers()->delete();
            foreach ($data['tiers'] ?? [] as $tier) {
                if (! empty($tier['min_qty']) && isset($tier['price']) && $tier['price'] !== '') {
                    $product->tiers()->create(['min_qty' => (int) $tier['min_qty'], 'price' => $tier['price']]);
                }
            }

            if (array_key_exists('taxes', $data)) {
                $product->taxes()->delete();
                foreach ((array) $data['taxes'] as $taxId => $row) {
                    if ((float) ($row['tax'] ?? 0) > 0) {
                        $product->taxes()->create(['tax_id' => (int) $taxId, 'tax' => (float) $row['tax'], 'tax_type' => ($row['tax_type'] ?? '') === 'flat' ? 'flat' : 'percent']);
                    }
                }
            }

            return $product->refresh();
        });
    }

    public function duplicate(Product $product, ?int $userId = null): Product
    {
        return DB::transaction(function () use ($product, $userId) {
            $copy = $product->replicate(['slug', 'num_of_sale', 'rating', 'review_count', 'legacy_id']);
            $copy->name = $product->name.' (Copy)';
            $copy->slug = null;
            $copy->barcode = $this->newBarcode();
            $copy->published = false;
            $copy->user_id = $userId ?? $product->user_id;
            $copy->save();

            $copy->categories()->sync($product->categories()->pluck('categories.id'));
            foreach ($product->stocks as $stock) {
                $copy->stocks()->create($stock->only(['variant', 'sku', 'price', 'qty', 'image_upload_id']));
            }
            foreach ($product->tiers as $tier) {
                $copy->tiers()->create($tier->only(['min_qty', 'price']));
            }
            $copy->refreshStockTotal();

            return $copy;
        });
    }

    /** 8 digit barcode that is not used yet. */
    public function newBarcode(): string
    {
        do {
            $code = (string) random_int(10000000, 99999999);
        } while (Product::query()->where('barcode', $code)->exists());

        return $code;
    }
}
