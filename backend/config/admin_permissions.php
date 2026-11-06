<?php

/*
| Every admin route is protected by one of these permission names.
| Grouped by section so the Role form can show them as checkbox groups.
| The seeder (PermissionSeeder) syncs this list into the permissions table.
*/

$crud = fn (string $name) => ["view_{$name}", "add_{$name}", "edit_{$name}", "delete_{$name}"];

return [
    'Dashboard' => ['view_dashboard'],
    'Products' => [...$crud('products'), 'bulk_import_products', 'bulk_export_products'],
    'Categories' => $crud('categories'),
    'Collections' => $crud('collections'),
    'Brands' => $crud('brands'),
    'Attributes' => [...$crud('attributes'), ...$crud('colors')],
    'Reviews' => ['view_reviews', 'approve_reviews', 'reply_reviews', 'delete_reviews'],
    'Orders' => [
        'view_orders', 'create_orders', 'edit_orders', 'delete_orders',
        'change_delivery_status', 'change_payment_status', 'send_courier', 'view_fraud_check',
        'log_calls', 'print_invoices', 'view_abandoned_carts', 'convert_abandoned_carts', 'delete_abandoned_carts',
    ],
    'Customers' => ['view_customers', 'edit_customers', 'ban_customers', 'delete_customers'],
    'Uploaded Files' => ['view_uploads', 'add_uploads', 'delete_uploads'],
    'Reports' => ['view_reports', 'export_reports'],
    'Marketing' => [...$crud('flash_deals'), ...$crud('coupons'), 'send_newsletter', 'view_subscribers', 'delete_subscribers', 'download_product_feed'],
    'Landing Page' => [...$crud('landing_pages'), 'view_landing_orders'],
    'Website Setup' => ['manage_header', 'manage_footer', 'manage_pages', 'manage_appearance'],
    'Shipping' => ['manage_shipping_configuration', 'manage_shipping_locations', 'manage_couriers'],
    'Setup and Configurations' => [
        'manage_general_settings', 'manage_features', 'manage_languages', 'manage_taxes',
        'manage_smtp', 'manage_payment_methods', 'manage_order_configuration', 'manage_file_system',
        'manage_social_login', 'manage_facebook', 'manage_google', 'clear_cache',
    ],
    'Staffs' => [...$crud('staffs'), 'manage_roles', 'manage_permissions'],
];
