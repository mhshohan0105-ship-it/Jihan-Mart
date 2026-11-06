<?php

namespace App\Providers;

use App\Models\BusinessSetting;
use App\Models\Category;
use App\Models\City;
use App\Models\Collection;
use App\Models\FlashDeal;
use App\Models\FlashDealProduct;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductTax;
use App\Models\Review;
use App\Models\State;
use App\Models\Tax;
use App\Models\User;
use App\Services\StorefrontService;
use App\Support\AdminMenu;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // super admin passes every permission check
        Gate::before(fn (User $user) => $user->isSuperAdmin() ? true : null);

        Paginator::defaultView('vendor.pagination.admin');

        // storefront orders, reviews and cart captures: per IP
        RateLimiter::for('storefront-writes', fn (Request $request) => Limit::perMinute(20)->by($request->ip()));

        // anything the storefront shows invalidates its cached payload
        foreach ([Product::class, ProductStock::class, Category::class, Collection::class, Review::class, BusinessSetting::class, State::class, City::class, FlashDeal::class, FlashDealProduct::class, Page::class, Tax::class, ProductTax::class] as $model) {
            $model::saved(fn () => StorefrontService::flush());
            $model::deleted(fn () => StorefrontService::flush());
        }

        View::composer('components.layouts.admin', function ($view) {
            $view->with('adminMenu', AdminMenu::for(auth()->user()));
        });
    }
}
