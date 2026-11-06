<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\Collection;
use App\Models\Color;
use Illuminate\Database\Seeder;

/** Starter colours, attributes and collections (safe to run again). */
class CatalogDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'Black' => '#000000', 'White' => '#ffffff', 'Red' => '#e11d48', 'Pink' => '#ec4899', 'Orange' => '#f47c20',
            'Yellow' => '#facc15', 'Green' => '#16a34a', 'Blue' => '#2563eb', 'Navy' => '#1b2b57', 'Purple' => '#7c3aed',
            'Brown' => '#92400e', 'Golden' => '#d4a017', 'Silver' => '#c0c0c0', 'Nude' => '#e3bc9a',
        ] as $name => $code) {
            Color::query()->firstOrCreate(['name' => $name], ['code' => $code]);
        }

        foreach ([
            'Size' => ['S', 'M', 'L', 'XL', 'XXL', 'Free Size'],
            'Shade' => ['01', '02', '03', '04', '05'],
            'Pcs' => ['1 Pcs', '2 Pcs', '3 Pcs', '5 Pcs', '10 Pcs'],
            'Design' => ['Design 1', 'Design 2', 'Design 3'],
            'Fragrance' => ['Rose', 'Lavender', 'Vanilla'],
            'Flavor' => ['Chocolate', 'Strawberry', 'Mango'],
        ] as $name => $values) {
            $attr = Attribute::query()->firstOrCreate(['name' => $name]);
            foreach ($values as $value) {
                $attr->values()->firstOrCreate(['value' => $value]);
            }
        }

        foreach (['Free Delivery', 'Shop 2000 Get Free Delivery', 'Up To 50% Off', 'Mystery Box', '99 Tk Offer'] as $name) {
            Collection::query()->firstOrCreate(['name' => $name], ['status' => true]);
        }
    }
}
