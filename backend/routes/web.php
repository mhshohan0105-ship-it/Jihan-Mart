<?php

use App\Http\Controllers\Admin\MarketingController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

// the storefront (single page, data from the same cached payload as /api/v1), or a landing page on its own domain
Route::get('/', [SiteController::class, 'home'])->name('home');
Route::get('page/{slug}', [SiteController::class, 'page'])->name('page');
Route::get('product/{slug}', [SiteController::class, 'product'])->name('product.show');
Route::get('sitemap.xml', [SiteController::class, 'sitemap'])->name('sitemap');
Route::get('robots.txt', fn () => response("User-agent: *\nDisallow: /admin\nDisallow: /api/\n\nSitemap: ".url('/sitemap.xml')."\n")->header('Content-Type', 'text/plain'))->name('robots');
// stable catalog URL for Facebook / Google Merchant
Route::get('feeds/products.csv', [MarketingController::class, 'feed'])->middleware('throttle:10,1')->name('feeds.products');

Route::middleware('locale')->group(function () {
    Route::get('login', [LoginController::class, 'show'])->name('login');
    Route::post('login', [LoginController::class, 'login'])->middleware('throttle:20,1')->name('login.attempt');
});
Route::post('logout', [LoginController::class, 'logout'])->name('logout');
