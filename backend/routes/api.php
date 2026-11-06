<?php

use App\Http\Controllers\Api\CourierWebhookController;
use App\Http\Controllers\Api\StorefrontController;
use Illuminate\Support\Facades\Route;

/*
| Storefront API, mounted at /api/v1 (see bootstrap/app.php). No login needed.
| Writes are throttled per IP and carry a honeypot field.
*/

Route::controller(StorefrontController::class)->group(function () {
    Route::get('catalog', 'catalog')->name('catalog');
    Route::get('settings', 'settings')->name('settings');
    Route::get('locations', 'locations')->name('locations');
    Route::get('products/{product}', 'product')->name('product');
    Route::get('orders/track', 'track')->middleware('throttle:30,1')->name('orders.track');
    Route::post('reviews/verify', 'verifyReview')->middleware('throttle:20,1')->name('reviews.verify');
    Route::get('reviews', 'reviews')->name('reviews');
    Route::post('coupons/validate', 'validateCoupon')->middleware('throttle:30,1')->name('coupons.validate');

    Route::middleware('throttle:storefront-writes')->group(function () {
        Route::post('orders', 'placeOrder')->name('orders.store');
        Route::post('abandoned-carts', 'abandoned')->name('abandoned');
        Route::post('reviews', 'storeReview')->name('reviews.store');
        Route::post('reviews/{review}/helpful', 'helpful')->name('reviews.helpful');
        Route::post('subscribers', 'subscribe')->name('subscribers.store');
    });
});

Route::post('webhooks/steadfast', [CourierWebhookController::class, 'steadfast'])->middleware('throttle:120,1')->name('webhooks.steadfast');
