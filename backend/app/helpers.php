<?php

use App\Models\BusinessSetting;
use App\Models\Upload;
use Illuminate\Support\Carbon;

if (! function_exists('get_setting')) {
    /** Read a business setting; pass $lang for translatable values (falls back to the untranslated row). */
    function get_setting(string $type, mixed $default = null, ?string $lang = null): mixed
    {
        $all = BusinessSetting::allCached();
        if ($lang && array_key_exists($type.'.'.$lang, $all)) {
            return $all[$type.'.'.$lang];
        }

        return $all[$type] ?? $default;
    }
}

if (! function_exists('setting_on')) {
    /** True when a switch style setting is turned on ("1"). */
    function setting_on(string $type, bool $default = false): bool
    {
        $value = get_setting($type);

        return $value === null ? $default : (string) $value === '1';
    }
}

if (! function_exists('cleanBdPhone')) {
    /**
     * Normalise a Bangladeshi mobile number to 01XXXXXXXXX so the same customer always matches.
     * Strips +88 / 88, spaces, dashes and brackets. Returns null when it is not a valid BD mobile.
     */
    function cleanBdPhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }
        // Bangla digits typed on a Bangla keyboard
        $phone = strtr($phone, ['০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4', '৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9']);
        $digits = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($digits, '880')) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '88') && strlen($digits) === 13) {
            $digits = substr($digits, 2);
        }

        return preg_match('/^01[3-9]\d{8}$/', $digits) ? $digits : null;
    }
}

if (! function_exists('format_price')) {
    /** ৳1,234.00 */
    function format_price(float|int|string|null $amount, int $decimals = 2): string
    {
        return '৳'.number_format((float) $amount, $decimals);
    }
}

if (! function_exists('format_date')) {
    /** 24-10-2026 03:45 PM in the app timezone (Asia/Dhaka). */
    function format_date(mixed $date, bool $withTime = true): string
    {
        if (! $date) {
            return '';
        }
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);

        return $date->timezone(config('app.timezone'))->format($withTime ? 'd-m-Y h:i A' : 'd-m-Y');
    }
}

if (! function_exists('uploaded_asset')) {
    /** Public URL of an upload id (first id when a comma list is given). */
    function uploaded_asset(int|string|null $id): ?string
    {
        $id = (int) strtok((string) $id, ',');
        if (! $id) {
            return null;
        }

        return Upload::query()->find($id)?->url;
    }
}
