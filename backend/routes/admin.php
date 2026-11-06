<?php

use App\Http\Controllers\Admin\AbandonedCartController;
use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\BusinessSettingController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CollectionController;
use App\Http\Controllers\Admin\ColorController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\CourierController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FlashDealController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\LandingPageController;
use App\Http\Controllers\Admin\MarketingController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductBulkController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\SetupController;
use App\Http\Controllers\Admin\ShippingController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\SystemController;
use App\Http\Controllers\Admin\ToggleController;
use App\Http\Controllers\Admin\UploadController;
use App\Http\Controllers\Admin\WebsiteSetupController;
use Illuminate\Support\Facades\Route;

/*
| Admin panel: loaded with prefix /admin, name admin., middleware web + auth + admin + locale.
| Every route names the permission it needs.
*/

// any staff member who can change at least one settings page may use the shared save endpoints
$settingsPermissions = 'permission:manage_general_settings|manage_features|manage_header|manage_footer|manage_pages|manage_appearance'
    .'|manage_shipping_configuration|manage_couriers|manage_smtp|manage_payment_methods|manage_order_configuration'
    .'|manage_file_system|manage_social_login|manage_facebook|manage_google';

Route::get('/', [DashboardController::class, 'index'])->middleware('permission:view_dashboard')->name('dashboard');
Route::get('soon/{page}', [DashboardController::class, 'soon'])->name('soon');

Route::post('business-settings/update', [BusinessSettingController::class, 'update'])->middleware($settingsPermissions)->name('business_settings.update');
Route::post('env-key-update', [BusinessSettingController::class, 'envUpdate'])->middleware($settingsPermissions)->name('env_key_update');

Route::get('general-setting', [SystemController::class, 'generalSettings'])->middleware('permission:manage_general_settings')->name('settings.general');
Route::post('clear-cache', [SystemController::class, 'clearCache'])->middleware('permission:clear_cache')->name('clear_cache');
Route::post('locale', [SystemController::class, 'locale'])->name('locale');
Route::get('profile', [SystemController::class, 'profile'])->name('profile');
Route::put('profile', [SystemController::class, 'updateProfile'])->name('profile.update');

// media library ("Uploaded Files") and the shared file picker
Route::prefix('uploaded-files')->name('uploads.')->controller(UploadController::class)->group(function () {
    Route::get('/', 'index')->middleware('permission:view_uploads')->name('index');
    Route::get('create', 'create')->middleware('permission:add_uploads')->name('create');
    Route::post('/', 'store')->middleware('permission:add_uploads')->name('store');
    Route::get('json', 'json')->middleware('permission:view_uploads')->name('json');
    Route::get('by-ids', 'byIds')->name('by_ids');
    Route::get('{upload}/download', 'download')->middleware('permission:view_uploads')->name('download');
    Route::delete('bulk', 'bulkDestroy')->middleware('permission:delete_uploads')->name('bulk_destroy');
    Route::delete('{upload}', 'destroy')->middleware('permission:delete_uploads')->name('destroy');
});

// ---------------- Phase 2: catalog ----------------

Route::post('toggle/{resource}/{id}', ToggleController::class)->name('toggle');

Route::controller(ProductController::class)->prefix('products')->name('products.')->group(function () {
    Route::get('all', 'index')->middleware('permission:view_products')->name('index');
    Route::get('create', 'create')->middleware('permission:add_products')->name('create');
    Route::post('/', 'store')->middleware('permission:add_products')->name('store');
    Route::post('combinations', 'combinations')->middleware('permission:add_products|edit_products')->name('combinations');
    Route::delete('bulk', 'bulkDestroy')->middleware('permission:delete_products')->name('bulk_destroy');
    Route::get('{product}/edit', 'edit')->middleware('permission:edit_products')->name('edit');
    Route::put('{product}', 'update')->middleware('permission:edit_products')->name('update');
    Route::post('{product}/duplicate', 'duplicate')->middleware('permission:add_products')->name('duplicate');
    Route::delete('{product}', 'destroy')->middleware('permission:delete_products')->name('destroy');
});

Route::controller(ProductBulkController::class)->group(function () {
    Route::get('product-bulk-upload/index', 'importForm')->middleware('permission:bulk_import_products')->name('products.import');
    Route::post('product-bulk-upload', 'import')->middleware('permission:bulk_import_products')->name('products.import.store');
    Route::get('product-bulk-upload/template', 'template')->middleware('permission:bulk_import_products')->name('products.import.template');
    Route::get('product-bulk-upload/category-ids', 'categoryIds')->middleware('permission:bulk_import_products')->name('products.import.categories');
    Route::get('product-bulk-upload/brand-ids', 'brandIds')->middleware('permission:bulk_import_products')->name('products.import.brands');
    Route::get('product-bulk-export', 'export')->middleware('permission:bulk_export_products')->name('products.export');
});

$crud = function (string $name, string $controller, array $except = ['show']) {
    Route::resource($name, $controller)->except($except)
        ->middlewareFor(['index', 'show'], "permission:view_{$name}")
        ->middlewareFor(['create', 'store'], "permission:add_{$name}")
        ->middlewareFor(['edit', 'update'], "permission:edit_{$name}")
        ->middlewareFor(['destroy'], "permission:delete_{$name}");
};
$crud('categories', CategoryController::class);
$crud('brands', BrandController::class, ['show', 'create']);
$crud('attributes', AttributeController::class, ['create']);
$crud('colors', ColorController::class, ['show', 'create']);
$crud('collections', CollectionController::class);

Route::controller(AttributeController::class)->group(function () {
    Route::get('attributes/{attribute}/values.json', 'values')->middleware('permission:add_products|edit_products|view_attributes')->name('attributes.values');
    Route::post('attributes/{attribute}/values', 'storeValue')->middleware('permission:edit_attributes')->name('attributes.values.store');
    Route::put('attribute-values/{value}', 'updateValue')->middleware('permission:edit_attributes')->name('attributes.values.update');
    Route::delete('attribute-values/{value}', 'destroyValue')->middleware('permission:edit_attributes')->name('attributes.values.destroy');
});
Route::post('colors-filter-activation', [ColorController::class, 'filterActivation'])->middleware('permission:edit_colors')->name('colors.filter_activation');
Route::post('collections/{collection}/products/{product}', [CollectionController::class, 'toggleProduct'])->middleware('permission:edit_products')->name('collections.toggle_product');

Route::controller(ReviewController::class)->prefix('reviews')->name('reviews.')->group(function () {
    Route::get('/', 'index')->middleware('permission:view_reviews')->name('index');
    Route::post('auto-approve', 'autoApprove')->middleware('permission:approve_reviews')->name('auto_approve');
    Route::post('{review}/status', 'status')->middleware('permission:approve_reviews')->name('status');
    Route::post('{review}/reply', 'reply')->middleware('permission:reply_reviews')->name('reply');
    Route::delete('{review}', 'destroy')->middleware('permission:delete_reviews')->name('destroy');
});

// ---------------- Phase 3: shipping, orders, customers ----------------

Route::controller(ShippingController::class)->name('shipping.')->group(function () {
    Route::get('shipping_configuration', 'configuration')->middleware('permission:manage_shipping_configuration')->name('configuration');
    Route::post('shipping_configuration', 'saveConfiguration')->middleware('permission:manage_shipping_configuration')->name('configuration.save');
    Route::middleware('permission:manage_shipping_locations')->group(function () {
        Route::get('countries', 'countries')->name('countries');
        Route::post('countries/{country}/toggle', 'toggleCountry')->name('countries.toggle');
        Route::get('states', 'states')->name('states');
        Route::post('states', 'saveState')->name('states.store');
        Route::put('states/{state}', 'saveState')->name('states.update');
        Route::post('states/{state}/toggle', 'toggleState')->name('states.toggle');
        Route::delete('states/{state}', 'destroyState')->name('states.destroy');
        Route::get('cities', 'cities')->name('cities');
        Route::post('cities', 'saveCity')->name('cities.store');
        Route::put('cities/{city}', 'saveCity')->name('cities.update');
        Route::post('cities/{city}/toggle', 'toggleCity')->name('cities.toggle');
        Route::delete('cities/{city}', 'destroyCity')->name('cities.destroy');
    });
});
Route::post('get-cities', [ShippingController::class, 'getCities'])->name('get_cities');

Route::controller(OrderController::class)->group(function () {
    Route::get('all_orders/{status?}', 'index')->middleware('permission:view_orders')->name('orders.index');
    Route::get('orders/create', 'create')->middleware('permission:create_orders')->name('orders.create');
    Route::post('orders', 'store')->middleware('permission:create_orders')->name('orders.store');
    Route::get('orders/pos-products', 'posProducts')->middleware('permission:create_orders|edit_orders')->name('orders.pos_products');
    Route::get('orders/customer-search', 'customerSearch')->middleware('permission:create_orders|edit_orders')->name('orders.customer_search');
    Route::get('pos-order-edit/{order}', 'edit')->middleware('permission:edit_orders')->name('orders.edit');
    Route::put('orders/{order}', 'update')->middleware('permission:edit_orders')->name('orders.update');
    Route::post('orders/update_delivery_status', 'deliveryStatus')->middleware('permission:change_delivery_status')->name('orders.delivery_status');
    Route::post('orders/update_payment_status', 'paymentStatus')->middleware('permission:change_payment_status')->name('orders.payment_status');
    Route::post('orders/update_tracking_code', 'trackingCode')->middleware('permission:edit_orders')->name('orders.tracking_code');
    Route::post('orders/update_admin_note', 'note')->middleware('permission:view_orders')->name('orders.note');
    Route::post('orders/log-call', 'logCall')->middleware('permission:log_calls')->name('orders.log_call');
    Route::post('orders/add-call-note', 'callNote')->middleware('permission:log_calls')->name('orders.call_note');
    Route::get('orders/{order}/calls', 'calls')->middleware('permission:view_orders')->name('orders.calls');
    Route::get('orders/{order}/activities', 'activities')->middleware('permission:view_orders')->name('orders.activities');
    Route::delete('orders/bulk', 'bulkDestroy')->middleware('permission:delete_orders')->name('orders.bulk_destroy');
    Route::delete('orders/{order}', 'destroy')->middleware('permission:delete_orders')->name('orders.destroy');
    Route::get('orders/{order}', 'show')->middleware('permission:view_orders')->name('orders.show');
});
Route::get('invoice', [InvoiceController::class, 'invoices'])->middleware('permission:print_invoices')->name('orders.invoice');
Route::get('orders-packaging-checklist', [InvoiceController::class, 'checklist'])->middleware('permission:print_invoices')->name('orders.checklist');

Route::controller(AbandonedCartController::class)->prefix('abandoned-carts')->name('abandoned.')->group(function () {
    Route::get('/', 'index')->middleware('permission:view_abandoned_carts')->name('index');
    Route::post('{cart}/convert', 'convert')->middleware('permission:convert_abandoned_carts')->name('convert');
    Route::delete('{cart}', 'destroy')->middleware('permission:delete_abandoned_carts')->name('destroy');
});

Route::controller(CustomerController::class)->prefix('customers')->name('customers.')->group(function () {
    Route::get('/', 'index')->middleware('permission:view_customers')->name('index');
    Route::delete('bulk', 'bulkDestroy')->middleware('permission:delete_customers')->name('bulk_destroy');
    Route::get('{customer}', 'show')->middleware('permission:view_customers')->name('show');
    Route::post('{customer}/ban', 'ban')->middleware('permission:ban_customers')->name('ban');
    Route::delete('{customer}', 'destroy')->middleware('permission:delete_customers')->name('destroy');
});

// ---------------- Phase 5: dashboard + reports ----------------

Route::get('dashboard/realtime', [DashboardController::class, 'realtime'])->middleware('permission:view_dashboard')->name('dashboard.realtime');
Route::controller(ReportController::class)->middleware('permission:view_reports')->group(function () {
    Route::get('date-wise-sale-report', 'dateWise')->name('reports.date_wise');
    Route::get('sale-summary-report', 'summary')->name('reports.summary');
    Route::get('in_house_sale_report', 'productSale')->name('reports.product_sale');
    Route::get('stock_report', 'stock')->name('reports.stock');
});

// ---------------- Phase 4: couriers ----------------

Route::controller(CourierController::class)->group(function () {
    Route::get('courier-settings', 'settings')->middleware('permission:manage_couriers')->name('couriers.settings');
    Route::post('courier-settings', 'saveSettings')->middleware('permission:manage_couriers')->name('couriers.settings.save');
    Route::post('courier-settings/test/{courier}', 'test')->middleware('permission:manage_couriers')->name('couriers.test');
    Route::get('pathao-locations', 'pathaoLocations')->middleware('permission:manage_couriers|manage_shipping_locations')->name('couriers.pathao_locations');
    Route::post('cities/{city}/pathao', 'savePathaoMapping')->middleware('permission:manage_shipping_locations')->name('couriers.pathao_mapping');
    Route::post('create-order-courier', 'send')->middleware('permission:send_courier')->name('couriers.send');
    Route::get('orders-fraud/check', 'fraud')->middleware('permission:view_fraud_check')->name('couriers.fraud');
    Route::post('orders-fraud/batch', 'fraudBatch')->middleware('permission:view_fraud_check')->name('couriers.fraud_batch');
});

// ---------------- Phase 6: marketing ----------------

foreach (['flash-deals' => [FlashDealController::class, 'flash_deals', 'flashDeal'], 'coupons' => [CouponController::class, 'coupons', 'coupon']] as $uri => [$controller, $perm, $param]) {
    Route::controller($controller)->prefix($uri)->name(str_replace('-', '_', $uri).'.')->group(function () use ($perm, $param) {
        Route::get('/', 'index')->middleware("permission:view_$perm")->name('index');
        Route::get('create', 'create')->middleware("permission:add_$perm")->name('create');
        Route::post('/', 'store')->middleware("permission:add_$perm")->name('store');
        Route::get("{{$param}}/edit", 'edit')->middleware("permission:edit_$perm")->name('edit');
        Route::put("{{$param}}", 'update')->middleware("permission:edit_$perm")->name('update');
        Route::delete("{{$param}}", 'destroy')->middleware("permission:delete_$perm")->name('destroy');
    });
}
Route::controller(MarketingController::class)->group(function () {
    Route::get('subscribers', 'subscribers')->middleware('permission:view_subscribers')->name('subscribers.index');
    Route::delete('subscribers/{subscriber}', 'destroySubscriber')->middleware('permission:delete_subscribers')->name('subscribers.destroy');
    Route::get('newsletter', 'newsletter')->middleware('permission:send_newsletter')->name('newsletter.index');
    Route::post('newsletter', 'sendNewsletter')->middleware('permission:send_newsletter')->name('newsletter.send');
    Route::get('product-feed.csv', 'feed')->middleware('permission:download_product_feed')->name('product_feed');
});

// ---------------- Phase 7: website setup + landing pages ----------------

Route::controller(WebsiteSetupController::class)->group(function () {
    Route::get('website/header', 'header')->middleware('permission:manage_header')->name('website.header');
    Route::get('website/footer', 'footer')->middleware('permission:manage_footer')->name('website.footer');
    Route::get('website/appearance', 'appearance')->middleware('permission:manage_appearance')->name('website.appearance');
    Route::middleware('permission:manage_pages')->prefix('website/pages')->name('pages.')->group(function () {
        Route::get('/', 'pages')->name('index');
        Route::get('create', 'createPage')->name('create');
        Route::post('/', 'storePage')->name('store');
        Route::get('{page}/edit', 'editPage')->name('edit');
        Route::put('{page}', 'updatePage')->name('update');
        Route::delete('{page}', 'destroyPage')->name('destroy');
    });
});
Route::controller(LandingPageController::class)->prefix('landing-home-page')->name('landing_pages.')->group(function () {
    Route::get('/', 'index')->middleware('permission:view_landing_pages')->name('index');
    Route::get('create', 'create')->middleware('permission:add_landing_pages')->name('create');
    Route::post('/', 'store')->middleware('permission:add_landing_pages')->name('store');
    Route::get('{landingPage}/edit', 'edit')->middleware('permission:edit_landing_pages')->name('edit');
    Route::put('{landingPage}', 'update')->middleware('permission:edit_landing_pages')->name('update');
    Route::delete('{landingPage}', 'destroy')->middleware('permission:delete_landing_pages')->name('destroy');
});
Route::get('landing-page-order-index', [OrderController::class, 'landing'])->middleware('permission:view_landing_orders')->name('landing_orders');

// ---------------- Phase 8: setup & configurations, staffs ----------------

Route::controller(SetupController::class)->group(function () {
    Route::get('features-activation', 'features')->middleware('permission:manage_features')->name('features.index');
    Route::post('features-activation', 'toggleFeature')->middleware('permission:manage_features')->name('features.toggle');
    Route::post('smtp-settings/test', 'testEmail')->middleware('permission:manage_smtp')->name('setup.test_email');
    Route::middleware('permission:manage_languages')->group(function () {
        Route::get('languages', 'languages')->name('languages.index');
        Route::get('languages/{code}/translations', 'translationEditor')->name('languages.translations');
        Route::post('languages/{code}/translations', 'saveTranslations')->name('languages.translations.save');
    });
    Route::middleware('permission:manage_taxes')->group(function () {
        Route::get('tax', 'taxes')->name('taxes.index');
        Route::post('tax', 'saveTax')->name('taxes.store');
        Route::put('tax/{tax}', 'saveTax')->name('taxes.update');
        Route::delete('tax/{tax}', 'destroyTax')->name('taxes.destroy');
    });
    // plain settings forms (permission checked per page inside)
    Route::get('setup/{page}', 'page')->name('setup.page');
});

Route::controller(StaffController::class)->group(function () {
    Route::get('staffs', 'index')->middleware('permission:view_staffs')->name('staffs.index');
    Route::get('staffs/create', 'create')->middleware('permission:add_staffs')->name('staffs.create');
    Route::post('staffs', 'store')->middleware('permission:add_staffs')->name('staffs.store');
    Route::get('staffs/{staff}/edit', 'edit')->middleware('permission:edit_staffs')->name('staffs.edit');
    Route::put('staffs/{staff}', 'update')->middleware('permission:edit_staffs')->name('staffs.update');
    Route::delete('staffs/{staff}', 'destroy')->middleware('permission:delete_staffs')->name('staffs.destroy');
    Route::middleware('permission:manage_roles')->prefix('roles')->name('roles.')->group(function () {
        Route::get('/', 'roles')->name('index');
        Route::get('create', 'createRole')->name('create');
        Route::post('/', 'storeRole')->name('store');
        Route::get('{role}/edit', 'editRole')->name('edit');
        Route::put('{role}', 'updateRole')->name('update');
        Route::delete('{role}', 'destroyRole')->name('destroy');
    });
    Route::get('permissions', 'permissions')->middleware('permission:manage_permissions')->name('permissions.index');
});
