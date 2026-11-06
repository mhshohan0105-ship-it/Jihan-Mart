<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

/**
 * Writes secrets (SMTP, payment, courier keys) into .env.
 * Only keys listed in config('admin_env_keys') may be written, values are never shown back in full.
 */
class EnvWriter
{
    public static function set(array $values): void
    {
        $path = app()->environmentFilePath();
        $env = File::exists($path) ? File::get($path) : '';

        foreach ($values as $key => $value) {
            $key = strtoupper($key);
            if (! in_array($key, config('admin_env_keys', []), true)) {
                continue;
            }
            $line = $key.'='.self::quote((string) $value);
            $pattern = '/^'.preg_quote($key, '/').'=.*$/m';
            $env = preg_match($pattern, $env)
                ? preg_replace($pattern, str_replace(['\\', '$'], ['\\\\', '\\$'], $line), $env)
                : rtrim($env).PHP_EOL.$line.PHP_EOL;
        }

        File::put($path, $env);
    }

    /** Current value straight from the .env file (works even when config is cached). */
    public static function get(string $key): ?string
    {
        $path = app()->environmentFilePath();
        if (! File::exists($path) || ! preg_match('/^'.preg_quote($key, '/').'=(.*)$/m', File::get($path), $m)) {
            return null;
        }
        $value = trim($m[1]);
        if (strlen($value) > 1 && $value[0] === '"' && str_ends_with($value, '"')) {
            $value = stripcslashes(substr($value, 1, -1));
        }

        return in_array($value, ['', 'null'], true) ? null : $value;
    }

    /** "abc…xyz" style preview for the settings forms. */
    public static function mask(?string $value): string
    {
        if (! $value) {
            return '';
        }

        return strlen($value) <= 6 ? str_repeat('•', 6) : substr($value, 0, 3).str_repeat('•', 6).substr($value, -3);
    }

    private static function quote(string $value): string
    {
        $value = str_replace(["\r", "\n"], '', $value);

        return preg_match('/[\s#"\'$]/', $value) ? '"'.str_replace('"', '\"', $value).'"' : $value;
    }
}
