<?php

namespace App\Services;

use App\Http\Controllers\Api\StorefrontController;
use App\Models\Category;
use App\Models\FlashDeal;
use App\Models\Page;
use App\Models\Product;
use App\Models\State;
use Illuminate\Support\Facades\Cache;

/**
 * Data the storefront needs on every page, in the compact shape the storefront JS reads.
 * Cached; the cache is cleared whenever a product, category, setting or location changes.
 */
class StorefrontService
{
    public const CACHE_KEY = 'storefront_payload';

    /** Card colours for categories, cycled in order. */
    private const PALETTE = ['#f47c20,#b8540b', '#1b2b57,#2c4a8f', '#3f8f3a,#14532d', '#6a3d9a,#3b1f5c', '#ec4899,#831843',
        '#0ea5e9,#0c4a6e', '#f59e0b,#7c2d12', '#14b8a6,#134e4a', '#ef4444,#7f1d1d', '#8b5cf6,#312e81'];

    public function payload(): array
    {
        // expire early when a flash deal starts or ends so prices switch on time
        $next = FlashDeal::query()->where('status', true)->where(fn ($q) => $q->where('start_date', '>', now())->orWhere('end_date', '>', now()))
            ->get(['start_date', 'end_date'])->flatMap(fn ($d) => [$d->start_date, $d->end_date])->filter(fn ($t) => $t > now())->min();
        $ttl = $next && $next < now()->addMinutes(10) ? $next->copy()->addSecond() : now()->addMinutes(10);

        return Cache::remember(self::CACHE_KEY, $ttl, fn () => [
            'catalog' => $this->catalog(),
            'settings' => $this->settings(),
            'locations' => $this->locations(),
        ]);
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function catalog(): array
    {
        $tops = Category::query()->whereNull('parent_id')->with('children')->orderByDesc('order_level')->orderBy('name')->get();
        $products = Product::query()->where('published', true)->with(['categories:id,parent_id', 'thumbnail', 'stocks'])->latest('id')->get();

        $categories = [];
        $productRows = [];
        $topOf = []; // category id => top level id
        foreach ($tops as $top) {
            $topOf[$top->id] = $top->id;
            foreach ($top->children as $child) {
                $topOf[$child->id] = $top->id;
            }
        }

        $counts = [];
        foreach ($products as $p) {
            $catIds = $p->categories->pluck('id')->all();
            $top = null;
            $sub = null;
            foreach ($catIds as $id) {
                if (! isset($topOf[$id])) {
                    continue;
                }
                $top ??= $topOf[$id];
                if ($topOf[$id] !== $id && $topOf[$id] === $top) {
                    $sub = $id;
                }
            }
            if (! $top) {
                continue;
            }
            $counts[$top] = ($counts[$top] ?? 0) + 1;
            $final = $p->finalPrice();
            $productRows[] = [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'img' => $p->thumbnailUrl(),
                'price' => $final,
                'old' => $final < (float) $p->unit_price ? (float) $p->unit_price : 0,
                'sales' => $p->num_of_sale,
                'stock' => $p->current_stock,
                'cat' => $top,
                'sub' => $sub,
                'rating' => (float) $p->rating,
                'reviews' => $p->review_count,
                'policy' => $p->payment_policy,
                'variants' => $p->variant_product ? $p->stocks->map(fn ($s) => ['v' => $s->variant, 'price' => $p->finalPrice($s->price), 'qty' => $s->qty])->values()->all() : null,
            ];
        }

        foreach ($tops as $i => $top) {
            if (empty($counts[$top->id])) {
                continue;
            }
            $categories[] = [
                'id' => $top->id,
                'en' => $top->name,
                'bn' => $top->name_bn ?: $top->name,
                'emoji' => $top->icon ?: '🛍️',
                'color' => self::PALETTE[$i % count(self::PALETTE)],
                'image' => uploaded_asset($top->image_upload_id),
                'count' => $counts[$top->id],
                'subs' => $top->children->map(fn ($c) => ['id' => $c->id, 'en' => $c->name, 'bn' => $c->name_bn ?: $c->name])->values()->all(),
            ];
        }

        $boxes = Product::query()->where('published', true)->whereIn('name', ['Mystery Box Mini', 'Mystery Box Plus', 'Mystery Box Royal'])
            ->orderBy('unit_price')->get(['id', 'name', 'unit_price']);

        return [
            'categories' => $categories,
            'products' => $productRows,
            'boxes' => $boxes->map(fn ($b) => ['id' => $b->id, 'name' => $b->name, 'price' => (float) $b->unit_price])->values()->all(),
            'flash' => $this->flashDeal(),
        ];
    }

    /** The featured running deal (or the one ending soonest) for the home page flash row. */
    private function flashDeal(): ?array
    {
        $deal = FlashDeal::query()->running()->with('items:id,flash_deal_id,product_id')->orderByDesc('featured')->orderBy('end_date')->first();

        return $deal ? [
            'title' => $deal->title,
            'ends' => $deal->end_date->toIso8601String(),
            'bg' => $deal->background_color,
            'text' => $deal->text_color,
            'banner' => uploaded_asset($deal->banner_upload_id),
            'ids' => $deal->items->pluck('product_id')->all(),
        ] : null;
    }

    public function settings(): array
    {
        return [
            'site_name' => get_setting('site_name', 'Jihan Mart'),
            'logo' => uploaded_asset(get_setting('system_logo_black')),
            'phone' => get_setting('contact_phone'),
            'email' => get_setting('contact_email'),
            'address' => get_setting('contact_address'),
            'socials' => [
                'facebook' => get_setting('facebook_link'), 'instagram' => get_setting('instagram_link'),
                'tiktok' => get_setting('tiktok_link'), 'whatsapp' => get_setting('whatsapp_number'),
                'messenger' => get_setting('messenger_link'), 'youtube' => get_setting('youtube_link'),
                'twitter' => get_setting('twitter_link'), 'linkedin' => get_setting('linkedin_link'),
            ],
            'free_shipping_min' => setting_on('free_shipping_enabled') ? (float) get_setting('free_shipping_min_amount', 0) : 0,
            'shipping_type' => get_setting('shipping_type', 'area_wise'),
            'flat_rate' => (float) get_setting('flat_rate_shipping_cost', 0),
            'review_auto_approve' => setting_on('review_auto_approve'),
            ...$this->websiteSetup(),
            ...$this->configuration(),
        ];
    }

    /** Setup & Configurations values the storefront needs (payments, minimum order, tracking, chat, map). */
    private function configuration(): array
    {
        $numbers = json_decode((string) get_setting('manual_payment_numbers'), true) ?: [];

        return [
            'payments' => collect(['cod', 'bkash', 'nagad', 'rocket'])->filter(fn ($m) => StorefrontController::paymentEnabled($m))->values()->all(),
            'payment_numbers' => array_values(array_filter(array_map(fn ($r) => ! empty($r['number']) ? [
                'gateway' => strtolower(trim($r['gateway'] ?? '')), 'number' => $r['number'], 'type' => $r['type'] ?? '',
            ] : null, $numbers))),
            'min_order' => setting_on('minimum_order_amount_check') ? (float) get_setting('minimum_order_amount', 0) : 0,
            'pixel_id' => setting_on('facebook_pixel') ? get_setting('facebook_pixel_id') : null,
            'messenger' => setting_on('facebook_chat') ? get_setting('facebook_page_id') : null,
            'map' => setting_on('google_map') ? (get_setting('google_map_query') ?: get_setting('contact_address')) : null,
        ];
    }

    /** Admin → Website Setup (header, footer, appearance, home page). Empty values keep the theme defaults. */
    private function websiteSetup(): array
    {
        $rows = fn (string $key) => json_decode((string) get_setting($key), true) ?: [];
        $images = fn (array $list) => array_values(array_filter(array_map(fn ($r) => ($img = uploaded_asset($r['image'] ?? null)) ? ['img' => $img, 'link' => $r['link'] ?? ''] : null, $list)));
        $pages = Page::query()->whereNotNull('position')->where('type', '!=', 'home')->whereNotNull('content')->where('content', '!=', '')->orderBy('id')->get(['title', 'slug', 'type', 'position']);

        return [
            'motto' => get_setting('site_motto'),
            'header' => [
                'logo' => uploaded_asset(get_setting('header_logo')),
                'sticky' => setting_on('enable_sticky_header', true),
                'lang_switcher' => setting_on('show_language_switcher', true),
                'helpline' => get_setting('helpline_number'),
                'announcements' => array_values(array_filter(array_column($rows('topbar_announcements'), 'text'))),
                'banner' => ($img = uploaded_asset(get_setting('topbar_banner'))) ? ['img' => $img, 'link' => get_setting('topbar_banner_link')] : null,
                'menu' => array_values(array_filter($rows('header_menu'), fn ($r) => ! empty($r['label']) && ! empty($r['link']))),
            ],
            'footer' => [
                'logo' => uploaded_asset(get_setting('footer_logo')),
                'about' => get_setting('about_us_description'),
                'copyright' => get_setting('frontend_copyright_text'),
                'play_store' => get_setting('play_store_link'),
                'app_store' => get_setting('app_store_link'),
                'show_socials' => setting_on('show_social_links', true),
                'links_title' => get_setting('widget_one_title'),
                'links' => [
                    ...array_values(array_filter($rows('widget_one_links'), fn ($r) => ! empty($r['label']) && ! empty($r['link']))),
                    ...$pages->map(fn ($p) => ['label' => $p->title, 'link' => $p->url()])->all(),
                ],
                'payment_images' => array_values(array_filter(array_map('uploaded_asset', array_filter(explode(',', (string) get_setting('payment_method_images')))))),
            ],
            'home' => [
                'slider' => $images($rows('home_slider')),
                'banners' => [$images($rows('home_banner1')), $images($rows('home_banner2')), $images($rows('home_banner3'))],
                'categories' => array_map('intval', $rows('home_categories')),
            ],
            'popup' => setting_on('show_website_popup') ? ['html' => get_setting('website_popup_content'), 'subscribe' => setting_on('show_subscribe_form')] : null,
            'cookies' => setting_on('show_cookies_agreement') ? get_setting('cookies_agreement_text') : null,
        ];
    }

    public function locations(): array
    {
        return State::query()->where('status', true)->with(['cities' => fn ($q) => $q->where('status', true)])->orderBy('id')->get()
            ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'cities' => $s->cities->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'cost' => (float) $c->cost])->values()->all()])
            ->all();
    }
}
