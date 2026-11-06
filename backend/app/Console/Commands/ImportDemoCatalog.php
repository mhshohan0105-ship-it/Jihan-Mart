<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Console\Command;

/** Loads ../data/catalog.js (the storefront's scraped demo catalog) into the database. Images stay as external URLs. */
class ImportDemoCatalog extends Command
{
    protected $signature = 'catalog:import-demo {--path= : catalog.js path} {--fresh : delete imported demo products first}';

    protected $description = 'Import the demo categories and products from data/catalog.js';

    public function handle(ProductService $products): int
    {
        $path = $this->option('path') ?: base_path('../data/catalog.js');
        if (! is_file($path)) {
            $this->error("Not found: {$path}");

            return self::FAILURE;
        }
        $json = preg_replace('/^.*?window\.CATALOG\s*=\s*/s', '', file_get_contents($path));
        $data = json_decode(rtrim(trim($json), ';'), true);
        if (! $data) {
            $this->error('Could not read the catalog JSON.');

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            Product::query()->whereNotNull('legacy_id')->delete();
        }

        // categories and sub categories, keyed by their old ids
        $catMap = [];
        $subMap = [];
        foreach ($data['categories'] as $c) {
            $cat = Category::query()->firstOrCreate(['name' => $c['en'], 'parent_id' => null]);
            $catMap[$c['id']] = $cat->id;
            foreach ($c['subs'] as $s) {
                $subMap[$s['id']] = Category::query()->firstOrCreate(['name' => $s['en'], 'parent_id' => $cat->id])->id;
            }
        }

        $bar = $this->output->createProgressBar(count($data['products']));
        $created = 0;
        foreach ($data['products'] as $p) {
            $bar->advance();
            if (Product::query()->where('legacy_id', $p['id'])->exists()) {
                continue;
            }
            $categoryIds = array_values(array_filter([$catMap[$p['cat']] ?? null, $subMap[$p['sub']] ?? null]));
            $hasDiscount = ($p['old'] ?? 0) > $p['price'];
            $products->save(new Product, [
                'name' => $p['name'],
                'category_ids' => $categoryIds,
                'unit_price' => $hasDiscount ? $p['old'] : $p['price'],
                'discount' => $hasDiscount ? $p['old'] - $p['price'] : 0,
                'discount_type' => 'flat',
                'qty' => max(0, (int) $p['stock']),
                'min_qty' => 1,
                'image_url' => $p['img'],
                'num_of_sale' => (int) $p['sales'],
                'legacy_id' => $p['id'],
                'published' => true,
                'payment_policy' => 'full_cod',
                'partial_payment_type' => 'flat',
            ]);
            $created++;
        }
        $bar->finish();
        $this->newLine();
        $this->info("Imported {$created} products into ".count($catMap).' categories.');

        return self::SUCCESS;
    }
}
