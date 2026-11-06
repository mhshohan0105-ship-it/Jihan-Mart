<?php

use App\Http\Middleware\ApiLocale;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SiteMode;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware(['web', 'auth', 'admin', 'locale'])
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
            Route::middleware('api')->prefix('api/v1')->name('api.')->group(base_path('routes/api.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureAdmin::class,
            'locale' => SetLocale::class,
            'permission' => PermissionMiddleware::class,
            'role' => RoleMiddleware::class,
        ]);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->appendToGroup('web', SiteMode::class);
        $middleware->appendToGroup('api', SiteMode::class);
        $middleware->appendToGroup('api', ApiLocale::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
