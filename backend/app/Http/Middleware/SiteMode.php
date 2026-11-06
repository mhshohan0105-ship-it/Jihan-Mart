<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Features activation → HTTPS redirect and Maintenance Mode for the public site (admin keeps working). */
class SiteMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if (setting_on('force_https') && ! $request->secure() && ! app()->environment('local', 'testing')) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        if (setting_on('maintenance_mode') && ! $this->bypass($request)) {
            return $request->expectsJson() || $request->is('api/*')
                ? response()->json(['message' => __('We are updating the shop. Please try again a little later.')], 503)
                : response()->view('maintenance', [], 503);
        }

        return $next($request);
    }

    private function bypass(Request $request): bool
    {
        // the panel, login and courier webhooks keep working; logged in staff can still browse the shop
        return $request->is('admin', 'admin/*', 'login', 'logout', 'api/v1/webhooks/*', 'up') || (bool) $request->user()?->canAccessAdmin();
    }
}
