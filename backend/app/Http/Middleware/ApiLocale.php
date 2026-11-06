<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Storefront API messages in the shopper's language: the site sends X-Locale (bn or en). */
class ApiLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = (string) $request->header('X-Locale');
        // setLocale() also rewrites config('app.locale'), so fall back to the fixed fallback locale
        app()->setLocale(array_key_exists($locale, SetLocale::LOCALES) ? $locale : config('app.fallback_locale', 'en'));

        return $next($request);
    }
}
