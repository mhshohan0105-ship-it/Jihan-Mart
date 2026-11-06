<?php

/*
| Admin sidebar. Each item:
|   label       English label (translated with __()).
|   icon        name from resources/views/components/icon.blade.php
|   route       route name; items not built yet use 'soon' => phase number instead
|   params      route parameters
|   permission  hidden when the user lacks it (super admin sees everything)
|   feature     business setting switch; hidden while that module is turned off
|   children    nested items
*/

$soon = fn (string $label, int $phase, string $permission, array $extra = []) => [
    'label' => $label, 'soon' => $phase, 'permission' => $permission, ...$extra,
];

return [
    ['label' => 'Dashboard', 'icon' => 'home', 'route' => 'admin.dashboard', 'permission' => 'view_dashboard'],

    ['label' => 'Products', 'icon' => 'cube', 'children' => [
        ['label' => 'Add New product', 'route' => 'admin.products.create', 'permission' => 'add_products'],
        ['label' => 'All Products', 'route' => 'admin.products.index', 'permission' => 'view_products'],
        ['label' => 'Bulk Import', 'route' => 'admin.products.import', 'permission' => 'bulk_import_products'],
        ['label' => 'Bulk Export', 'route' => 'admin.products.export', 'permission' => 'bulk_export_products'],
        ['label' => 'Category', 'route' => 'admin.categories.index', 'permission' => 'view_categories'],
        ['label' => 'Collections', 'route' => 'admin.collections.index', 'permission' => 'view_collections'],
        ['label' => 'Brand', 'route' => 'admin.brands.index', 'permission' => 'view_brands'],
        ['label' => 'Attribute', 'route' => 'admin.attributes.index', 'permission' => 'view_attributes'],
        ['label' => 'Colors', 'route' => 'admin.colors.index', 'permission' => 'view_colors'],
        ['label' => 'Product Reviews', 'route' => 'admin.reviews.index', 'permission' => 'view_reviews'],
    ]],

    ['label' => 'Sales', 'icon' => 'cart', 'children' => [
        ['label' => 'Create Order', 'route' => 'admin.orders.create', 'permission' => 'create_orders'],
        ['label' => 'All Orders', 'route' => 'admin.orders.index', 'permission' => 'view_orders'],
        ['label' => 'Abandoned Carts', 'route' => 'admin.abandoned.index', 'permission' => 'view_abandoned_carts'],
        ['label' => 'Pending Orders', 'route' => 'admin.orders.index', 'params' => ['pending'], 'permission' => 'view_orders'],
        ['label' => 'Confirmed Orders', 'route' => 'admin.orders.index', 'params' => ['confirmed'], 'permission' => 'view_orders'],
        ['label' => 'Picked-up Orders', 'route' => 'admin.orders.index', 'params' => ['picked_up'], 'permission' => 'view_orders'],
        ['label' => 'On The Way Orders', 'route' => 'admin.orders.index', 'params' => ['on_the_way'], 'permission' => 'view_orders'],
        ['label' => 'Delivered Orders', 'route' => 'admin.orders.index', 'params' => ['delivered'], 'permission' => 'view_orders'],
        ['label' => 'Cancelled Orders', 'route' => 'admin.orders.index', 'params' => ['cancelled'], 'permission' => 'view_orders'],
    ]],

    ['label' => 'Landing Page', 'icon' => 'globe', 'children' => [
        ['label' => 'Home Pages', 'route' => 'admin.landing_pages.index', 'permission' => 'view_landing_pages'],
        ['label' => 'Landing Page Orders', 'route' => 'admin.landing_orders', 'permission' => 'view_landing_orders'],
    ]],

    ['label' => 'Customers', 'icon' => 'users', 'children' => [
        ['label' => 'Customer list', 'route' => 'admin.customers.index', 'permission' => 'view_customers'],
    ]],

    ['label' => 'Uploaded Files', 'icon' => 'folder', 'route' => 'admin.uploads.index', 'permission' => 'view_uploads'],

    ['label' => 'Reports', 'icon' => 'chart', 'children' => [
        ['label' => 'Date Wise Sale Report', 'route' => 'admin.reports.date_wise', 'permission' => 'view_reports'],
        ['label' => 'Sale Summary Report', 'route' => 'admin.reports.summary', 'permission' => 'view_reports'],
        ['label' => 'Product Sale', 'route' => 'admin.reports.product_sale', 'permission' => 'view_reports'],
        ['label' => 'Products Stock', 'route' => 'admin.reports.stock', 'permission' => 'view_reports'],
    ]],

    ['label' => 'Marketing', 'icon' => 'megaphone', 'children' => [
        ['label' => 'Flash deals', 'route' => 'admin.flash_deals.index', 'permission' => 'view_flash_deals'],
        ['label' => 'Newsletters', 'route' => 'admin.newsletter.index', 'permission' => 'send_newsletter'],
        ['label' => 'Subscribers', 'route' => 'admin.subscribers.index', 'permission' => 'view_subscribers'],
        ['label' => 'Coupon', 'route' => 'admin.coupons.index', 'permission' => 'view_coupons'],
        ['label' => 'Product Csv Download', 'route' => 'admin.product_feed', 'permission' => 'download_product_feed'],
    ]],

    ['label' => 'Website Setup', 'icon' => 'desktop', 'children' => [
        ['label' => 'Header', 'route' => 'admin.website.header', 'permission' => 'manage_header'],
        ['label' => 'Footer', 'route' => 'admin.website.footer', 'permission' => 'manage_footer'],
        ['label' => 'Pages', 'route' => 'admin.pages.index', 'permission' => 'manage_pages'],
        ['label' => 'Appearance', 'route' => 'admin.website.appearance', 'permission' => 'manage_appearance'],
    ]],

    ['label' => 'Shipping', 'icon' => 'truck', 'children' => [
        ['label' => 'Shipping Configuration', 'route' => 'admin.shipping.configuration', 'permission' => 'manage_shipping_configuration'],
        ['label' => 'Courier API', 'route' => 'admin.couriers.settings', 'permission' => 'manage_couriers'],
        ['label' => 'Shipping Countries', 'route' => 'admin.shipping.countries', 'permission' => 'manage_shipping_locations'],
        ['label' => 'Shipping States', 'route' => 'admin.shipping.states', 'permission' => 'manage_shipping_locations'],
        ['label' => 'Shipping Cities', 'route' => 'admin.shipping.cities', 'permission' => 'manage_shipping_locations'],
    ]],

    ['label' => 'Setup & Configurations', 'icon' => 'cog', 'children' => [
        ['label' => 'General Settings', 'route' => 'admin.settings.general', 'permission' => 'manage_general_settings'],
        ['label' => 'Pos Configuration', 'route' => 'admin.setup.page', 'params' => ['pos-configuration'], 'permission' => 'manage_general_settings'],
        ['label' => 'Features activation', 'route' => 'admin.features.index', 'permission' => 'manage_features'],
        ['label' => 'Languages', 'route' => 'admin.languages.index', 'permission' => 'manage_languages'],
        ['label' => 'Vat & TAX', 'route' => 'admin.taxes.index', 'permission' => 'manage_taxes'],
        ['label' => 'SMTP Settings', 'route' => 'admin.setup.page', 'params' => ['smtp-settings'], 'permission' => 'manage_smtp'],
        ['label' => 'Payment Methods', 'route' => 'admin.setup.page', 'params' => ['payment-method'], 'permission' => 'manage_payment_methods'],
        ['label' => 'Order Configuration', 'route' => 'admin.setup.page', 'params' => ['order-configuration'], 'permission' => 'manage_order_configuration'],
        ['label' => 'File System & Cache Configuration', 'route' => 'admin.setup.page', 'params' => ['file-system'], 'permission' => 'manage_file_system'],
        ['label' => 'Social media Logins', 'route' => 'admin.setup.page', 'params' => ['social-login'], 'permission' => 'manage_social_login'],
        ['label' => 'Facebook', 'children' => [
            ['label' => 'Facebook Chat', 'route' => 'admin.setup.page', 'params' => ['facebook-chat'], 'permission' => 'manage_facebook'],
            ['label' => 'Facebook Comment', 'route' => 'admin.setup.page', 'params' => ['facebook-comment'], 'permission' => 'manage_facebook'],
        ]],
        ['label' => 'Google', 'children' => [
            ['label' => 'Analytics Tools', 'route' => 'admin.setup.page', 'params' => ['google-analytics'], 'permission' => 'manage_google'],
            ['label' => 'Google reCAPTCHA', 'route' => 'admin.setup.page', 'params' => ['google-recaptcha'], 'permission' => 'manage_google'],
            ['label' => 'Google Map', 'route' => 'admin.setup.page', 'params' => ['google-map'], 'permission' => 'manage_google'],
        ]],
    ]],

    ['label' => 'Staffs', 'icon' => 'shield', 'children' => [
        ['label' => 'All staffs', 'route' => 'admin.staffs.index', 'permission' => 'view_staffs'],
        ['label' => 'Staff permissions', 'route' => 'admin.roles.index', 'permission' => 'manage_roles'],
        ['label' => 'Permissions', 'route' => 'admin.permissions.index', 'permission' => 'manage_permissions'],
    ]],
];
