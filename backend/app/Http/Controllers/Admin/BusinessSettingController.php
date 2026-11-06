<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Support\EnvWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * Shared save endpoint for every settings page: the form posts types[] plus one input per type.
 * Arrays (repeating rows) are stored as JSON. Pass `lang` for translatable fields.
 */
class BusinessSettingController extends Controller
{
    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $types = array_unique((array) $request->input('types', []));
        $lang = $request->input('lang');
        $translatable = (array) $request->input('translatable', []);

        foreach ($types as $type) {
            if (! preg_match('/^[a-z0-9_]+$/', $type)) {
                continue;
            }
            // raw scripts run on every storefront page, so only Appearance managers may change them
            if (in_array($type, ['header_script', 'footer_script'], true) && ! $request->user()->can('manage_appearance')) {
                continue;
            }
            $value = $request->input($type);
            if (is_array($value)) {
                // repeating rows: drop rows left completely empty
                $value = array_values(array_filter($value, fn ($row) => ! is_array($row) || array_filter($row, fn ($v) => $v !== null && $v !== '')));
            }
            BusinessSetting::put($type, $value, in_array($type, $translatable, true) ? $lang : null);
        }

        // timezone is a config value, keep .env in sync so it applies everywhere
        if (in_array('system_timezone', $types, true) && in_array($request->input('system_timezone'), timezone_identifiers_list(), true)) {
            config(['admin_env_keys' => [...config('admin_env_keys'), 'APP_TIMEZONE']]);
            EnvWriter::set(['APP_TIMEZONE' => $request->input('system_timezone')]);
        }

        return $this->done($request, __('Settings updated successfully'));
    }

    /** Secrets go to .env, never to the database. Empty inputs keep the current value. */
    public function envUpdate(Request $request): JsonResponse|RedirectResponse
    {
        $values = [];
        foreach ((array) $request->input('types', []) as $key) {
            if (! $request->user()->can(self::envPermission((string) $key))) {
                continue;
            }
            $value = $request->input($key);
            if ($value !== null && $value !== '') {
                $values[$key] = $value;
            }
        }
        EnvWriter::set($values);
        Artisan::call('config:clear');

        return $this->done($request, __('Settings updated successfully'));
    }

    /** Each group of .env keys belongs to one settings page and its permission. */
    private static function envPermission(string $key): string
    {
        $map = [
            'MAIL' => 'manage_smtp', 'BKASH' => 'manage_payment_methods', 'NAGAD' => 'manage_payment_methods',
            'SSLCZ' => 'manage_payment_methods', 'AAMARPAY' => 'manage_payment_methods',
            'GOOGLE_CLIENT' => 'manage_social_login', 'FACEBOOK_CLIENT' => 'manage_social_login',
            'RECAPTCHA' => 'manage_google', 'GOOGLE_MAP' => 'manage_google',
            'CACHE' => 'manage_file_system', 'SESSION' => 'manage_file_system', 'REDIS' => 'manage_file_system',
            'PATHAO' => 'manage_couriers', 'STEADFAST' => 'manage_couriers',
        ];
        foreach ($map as $prefix => $permission) {
            if (str_starts_with(strtoupper($key), $prefix)) {
                return $permission;
            }
        }

        return 'manage_general_settings';
    }

    private function done(Request $request, string $message): JsonResponse|RedirectResponse
    {
        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => $message])
            : back()->with('success', $message);
    }
}
