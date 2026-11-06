<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            SettingSeeder::class,
            CatalogDefaultsSeeder::class,
            LocationSeeder::class,
            StorefrontSeeder::class,
            AdminSeeder::class,
        ]);
    }
}
