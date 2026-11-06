<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/** Syncs config/admin_permissions.php into the database and creates two starter roles. */
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $all = [];
        foreach (config('admin_permissions') as $section => $names) {
            foreach ($names as $name) {
                Permission::query()->updateOrCreate(
                    ['name' => $name, 'guard_name' => 'web'],
                    ['section' => $section],
                );
                $all[] = $name;
            }
        }
        // drop permissions that no longer exist in the config
        Permission::query()->whereNotIn('name', $all)->delete();

        Role::findOrCreate('Manager', 'web')->syncPermissions(
            array_values(array_filter($all, fn ($p) => ! str_starts_with($p, 'delete_staffs') && ! in_array($p, ['manage_roles', 'manage_permissions'], true)))
        );
        Role::findOrCreate('Order Staff', 'web')->syncPermissions([
            'view_dashboard', 'view_orders', 'create_orders', 'edit_orders', 'change_delivery_status',
            'change_payment_status', 'send_courier', 'view_fraud_check', 'log_calls', 'print_invoices',
            'view_abandoned_carts', 'convert_abandoned_carts', 'view_customers', 'view_products', 'view_uploads',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
