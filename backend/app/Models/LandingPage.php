<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['domain', 'logo_upload_id', 'sliders', 'video_link', 'features', 'banners', 'product_ids', 'contents', 'about', 'email', 'phone', 'address', 'status'])]
class LandingPage extends Model
{
    /** Banner groups and how many images each holds. */
    public const BANNERS = ['banner1' => 1, 'banner2' => 1, 'banner3' => 1, 'two' => 2, 'three' => 3];

    protected function casts(): array
    {
        return ['sliders' => 'array', 'features' => 'array', 'banners' => 'array', 'product_ids' => 'array', 'contents' => 'array', 'status' => 'boolean'];
    }

    /** "https://Shop.Example.com/" → "shop.example.com" */
    public static function normalizeDomain(string $domain): string
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('#^https?://#', '', $domain);

        return rtrim(explode('/', $domain)[0], '.');
    }

    public static function forHost(string $host): ?self
    {
        $host = self::normalizeDomain($host);

        return self::query()->where('status', true)->whereIn('domain', [$host, preg_replace('/^www\./', '', $host)])->first();
    }

    public function products(): EloquentCollection
    {
        return Product::query()->whereIn('id', $this->product_ids ?? [])->where('published', true)->with(['thumbnail', 'stocks'])->get();
    }

    /** Embed URL for YouTube / Facebook links, or null. */
    public function videoEmbed(): ?string
    {
        $link = (string) $this->video_link;
        if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))([\w-]{11})~', $link, $m)) {
            return 'https://www.youtube.com/embed/'.$m[1];
        }
        if (str_contains($link, 'facebook.com')) {
            return 'https://www.facebook.com/plugins/video.php?href='.urlencode($link);
        }

        return null;
    }
}
