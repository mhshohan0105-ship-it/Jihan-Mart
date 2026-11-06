<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Admin UI language from the session (English or Bangla). */
class SetLocale
{
    public const LOCALES = ['en' => 'English', 'bn' => 'বাংলা'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale', get_setting('default_language', config('app.locale')));
        if (array_key_exists($locale, self::LOCALES)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
