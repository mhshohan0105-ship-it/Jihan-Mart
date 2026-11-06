<?php

namespace App\Http\Controllers;

use App\Models\LandingPage;
use App\Models\Page;
use App\Models\Product;
use App\Services\StorefrontService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

/** Public pages: the storefront, custom pages and landing domains. */
class SiteController extends Controller
{
    public function home(Request $request, StorefrontService $store): View
    {
        // a landing domain pointed at this server shows its own page instead of the store
        if ($landing = LandingPage::forHost($request->getHost())) {
            return view('landing', [
                'page' => $landing,
                'products' => $landing->products(),
                'locations' => $store->locations(),
                'settings' => $store->settings(),
            ]);
        }

        return view('storefront', ['payload' => $store->payload()]);
    }

    /** A real page per product (shareable link, Google, Facebook); it reuses the storefront for cart and checkout. */
    public function product(string $slug, StorefrontService $store): View
    {
        $product = Product::query()->where('published', true)
            ->where(fn ($q) => $q->where('slug', $slug)->orWhere('id', ctype_digit($slug) ? (int) $slug : 0))
            ->with(['categories:id,name,name_bn,parent_id', 'brand:id,name', 'thumbnail'])
            ->firstOrFail();

        $images = collect(explode(',', (string) $product->photos))->filter()->map(fn ($id) => uploaded_asset((int) $id))
            ->prepend($product->thumbnailUrl())->filter()->unique()->values()->all();
        $price = $product->finalPrice();
        $reviews = $product->reviews()->where('status', 'approved')->with('photo')->latest('id')->limit(30)->get();
        $dist = collect([5, 4, 3, 2, 1])->mapWithKeys(fn ($n) => [$n => $reviews->where('rating', $n)->count()])->all();
        $plain = trim(preg_replace('/\s+/', ' ', strip_tags((string) $product->description)));
        $url = url('/product/'.$product->slug);
        $description = $product->meta_description ?: Str::limit($plain ?: $product->name.' — ৳'.$price.'। ক্যাশ অন ডেলিভারি, সারা বাংলাদেশে ডেলিভারি।', 160);

        return view('storefront', [
            'payload' => $store->payload(),
            'product' => [
                'model' => $product,
                'images' => $images,
                'price' => $price,
                'old' => $price < (float) $product->unit_price ? (float) $product->unit_price : 0,
                'category' => $product->categories->first(),
                'url' => $url,
                'page' => ['id' => $product->id, 'cat' => $product->categories->first()?->id], // for shop/app.js
                'reviews' => $reviews,
                'rating' => ['avg' => $reviews->count() ? round($reviews->avg('rating'), 1) : 0, 'count' => $reviews->count(), 'dist' => $dist],
                'meta_title' => ($product->meta_title ?: $product->name).' | '.get_setting('site_name', 'Jihan Mart'),
                'meta_description' => $description,
                // schema.org data so Google can show price and stock in results
                'schema' => [
                    '@context' => 'https://schema.org',
                    '@type' => 'Product',
                    'name' => $product->name,
                    'image' => $images,
                    'description' => $description,
                    'sku' => $product->sku ?: (string) $product->id,
                    'brand' => ['@type' => 'Brand', 'name' => $product->brand?->name ?? get_setting('site_name', 'Jihan Mart')],
                    'offers' => [
                        '@type' => 'Offer',
                        'url' => $url,
                        'priceCurrency' => 'BDT',
                        'price' => $price,
                        'availability' => $product->current_stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                        'itemCondition' => 'https://schema.org/NewCondition',
                    ],
                ] + ($reviews->count() ? [
                    // lets Google show the stars next to the product
                    'aggregateRating' => ['@type' => 'AggregateRating', 'ratingValue' => round($reviews->avg('rating'), 1), 'reviewCount' => $reviews->count(), 'bestRating' => 5, 'worstRating' => 1],
                    'review' => $reviews->take(5)->map(fn ($r) => [
                        '@type' => 'Review',
                        'author' => ['@type' => 'Person', 'name' => $r->name],
                        'datePublished' => $r->created_at->toDateString(),
                        'reviewBody' => $r->comment,
                        'reviewRating' => ['@type' => 'Rating', 'ratingValue' => $r->rating, 'bestRating' => 5],
                    ])->values()->all(),
                ] : []),
            ],
        ]);
    }

    /** Every public URL for search engines. */
    public function sitemap(): Response
    {
        $products = Product::query()->where('published', true)->latest('updated_at')->get(['slug', 'updated_at']);
        $pages = Page::query()->where('type', '!=', 'home')->whereNotNull('content')->where('content', '!=', '')->get(['slug', 'updated_at']);

        return response()->view('sitemap', compact('products', 'pages'))->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function page(string $slug, StorefrontService $store): View
    {
        $page = Page::query()->where('slug', $slug)->where('type', '!=', 'home')->firstOrFail();

        return view('page', ['page' => $page, 'settings' => $store->settings()]);
    }
}
