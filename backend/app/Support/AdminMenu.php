<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;

/** Turns config/admin_menu.php into the sidebar the current user may see. */
class AdminMenu
{
    public static function for(?User $user): array
    {
        return self::filter(config('admin_menu', []), $user);
    }

    private static function filter(array $items, ?User $user): array
    {
        $out = [];
        foreach ($items as $item) {
            if (! empty($item['feature']) && ! setting_on($item['feature'])) {
                continue;
            }
            if (! empty($item['permission']) && ! $user?->can($item['permission'])) {
                continue;
            }

            if (! empty($item['children'])) {
                $item['children'] = self::filter($item['children'], $user);
                if (! $item['children']) {
                    continue; // nothing inside is allowed, hide the group
                }
                $item['active'] = collect($item['children'])->contains('active', true);
            } else {
                [$item['url'], $item['active']] = self::link($item);
            }

            $out[] = $item;
        }

        return $out;
    }

    /** @return array{0: string, 1: bool} */
    private static function link(array $item): array
    {
        if (isset($item['soon'])) {
            $slug = Str::slug($item['label']);

            return [route('admin.soon', $slug), request()->routeIs('admin.soon') && request()->route('page') === $slug];
        }

        $params = $item['params'] ?? [];
        $url = route($item['route'], $params);
        $active = request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*');

        // several items share a route with different parameters (order status shortcuts)
        if ($active && request()->routeIs($item['route'])) {
            $current = array_values(array_filter(request()->route()?->parameters() ?? [], 'is_scalar'));
            $active = $current === array_values($params);
        }

        return [$url, $active];
    }

    /** Label and phase of a page that is not built yet (for the placeholder screen). */
    public static function soonPage(string $slug, ?array $items = null): ?array
    {
        foreach ($items ?? config('admin_menu', []) as $item) {
            if (isset($item['soon']) && Str::slug($item['label']) === $slug) {
                return $item;
            }
            if (! empty($item['children']) && $found = self::soonPage($slug, $item['children'])) {
                return $found;
            }
        }

        return null;
    }
}
