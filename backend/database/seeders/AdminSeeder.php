<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Super admin account. Email and password come from ADMIN_EMAIL / ADMIN_PASSWORD in .env;
 * when the password is missing a random one is generated and written to .env.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@jihanmart.test');
        $password = env('ADMIN_PASSWORD');

        if (! $password) {
            $password = Str::password(16, symbols: false);
            $path = app()->environmentFilePath();
            file_put_contents($path, rtrim((string) @file_get_contents($path)).PHP_EOL."ADMIN_EMAIL={$email}".PHP_EOL."ADMIN_PASSWORD={$password}".PHP_EOL);
            $this->command?->info('Admin password generated and saved to .env (ADMIN_PASSWORD).');
        }

        User::query()->updateOrCreate(['email' => $email], [
            'type' => User::TYPE_ADMIN,
            'name' => 'Admin',
            'password' => $password,
            'email_verified_at' => now(),
        ]);
    }
}
