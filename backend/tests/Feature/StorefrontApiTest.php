<?php

namespace Tests\Feature;

use App\Models\AbandonedCart;
use App\Models\BusinessSetting;
use App\Models\Category;
use App\Models\City;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Services\ProductService;
use Database\Seeders\LocationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** /api/v1 storefront endpoints and the storefront page. */
class StorefrontApiTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private City $city;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LocationSeeder::class);
        $cat = Category::query()->create(['name' => 'Cosmetic And Beauty', 'name_bn' => 'কসমেটিক', 'icon' => '💄']);
        $this->product = app(ProductService::class)->save(new Product, [
            'name' => 'Matte Lipstick', 'category_ids' => [$cat->id], 'unit_price' => 250, 'discount' => 50, 'discount_type' => 'flat',
            'qty' => 10, 'min_qty' => 1, 'payment_policy' => 'full_cod', 'partial_payment_type' => 'flat', 'published' => true,
        ]);
        $this->city = City::query()->where('name', 'Mirpur')->firstOrFail();
    }

    private function order(array $extra = [])
    {
        return $this->postJson('/api/v1/orders', [
            'name' => 'Sadia', 'phone' => '01712345678', 'address' => 'Mirpur 10',
            'state_id' => $this->city->state_id, 'city_id' => $this->city->id,
            'items' => [['product_id' => $this->product->id, 'quantity' => 2, 'price' => 1]], // price is ignored
            ...$extra,
        ]);
    }

    public function test_product_page_sitemap_and_robots(): void
    {
        $url = '/product/'.$this->product->slug;
        $this->get($url)->assertOk()
            ->assertSee('<title>Matte Lipstick | Jihan Mart</title>', false)
            ->assertSee('<link rel="canonical" href="'.url($url).'">', false)
            ->assertSee('"@type":"Product"', false)
            ->assertSee('৳200')
            ->assertSee('window.PRODUCT_PAGE = {"id":'.$this->product->id, false);
        $this->get('/product/'.$this->product->id)->assertOk(); // id links work too

        $this->product->update(['published' => false]);
        $this->get($url)->assertNotFound();
        $this->get('/product/no-such-thing')->assertNotFound();

        $this->product->update(['published' => true]);
        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->assertSee(url($url), false);
        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: '.url('/sitemap.xml'), false);
    }

    public function test_catalog_settings_locations_and_page(): void
    {
        $this->getJson('/api/v1/catalog')->assertOk()
            ->assertJsonPath('categories.0.bn', 'কসমেটিক')
            ->assertJsonPath('products.0.price', 200)
            ->assertJsonPath('products.0.old', 250);
        $this->getJson('/api/v1/settings')->assertOk()->assertJsonStructure(['site_name', 'free_shipping_min']);
        $this->getJson('/api/v1/locations')->assertOk()->assertJsonFragment(['name' => 'Mirpur', 'cost' => 60]);
        $this->getJson("/api/v1/products/{$this->product->slug}")->assertOk()->assertJsonPath('data.name', 'Matte Lipstick');
        $this->get('/')->assertOk()->assertSee('window.STORE', false)->assertSee('Matte Lipstick');
    }

    public function test_place_order_uses_server_prices_and_converts_abandoned_cart(): void
    {
        $this->postJson('/api/v1/abandoned-carts', ['phone' => '01712345678', 'name' => 'Sadia', 'items' => [['product_id' => $this->product->id, 'quantity' => 1]]])->assertOk();
        $this->assertSame(1, AbandonedCart::query()->where('status', 'pending')->count());

        $this->order()->assertCreated()->assertJsonPath('order.code', 1001)->assertJsonPath('order.total', 460);

        $order = Order::query()->firstOrFail();
        $this->assertSame('website', $order->source);
        $this->assertEquals(400, $order->subtotal);
        $this->assertSame(8, $this->product->fresh()->current_stock);
        $this->assertSame('converted', AbandonedCart::query()->first()->status);
        // the catalog shows the new stock straight away
        $this->getJson('/api/v1/catalog')->assertJsonPath('products.0.stock', 8);
    }

    public function test_honeypot_and_validation(): void
    {
        $this->order(['website' => 'http://spam'])->assertUnprocessable();
        $this->order(['phone' => '12345'])->assertUnprocessable()->assertJsonValidationErrors('phone');
        $this->assertSame(0, Order::query()->count());
    }

    public function test_prepaid_products_need_a_transaction_id(): void
    {
        $this->product->update(['payment_policy' => 'prepaid']);
        $this->order()->assertUnprocessable()->assertJsonValidationErrors('payment_method');
        $this->order(['payment_method' => 'bkash', 'transaction_id' => 'TRX123'])->assertCreated();
    }

    public function test_tracking_needs_matching_phone(): void
    {
        $this->order()->assertCreated();
        $this->getJson('/api/v1/orders/track?code=1001&phone=01712345678')->assertOk()->assertJsonPath('data.status', 'pending');
        $this->getJson('/api/v1/orders/track?code=1001&phone=01999999999')->assertNotFound();
    }

    public function test_reviews_wait_for_approval_unless_auto_approve(): void
    {
        $this->postJson('/api/v1/reviews', ['name' => 'Nusrat', 'rating' => 5, 'comment' => 'Lovely shop, quick reply'])
            ->assertCreated()->assertJsonPath('status', 'pending');
        $this->getJson('/api/v1/reviews')->assertJsonCount(0, 'data');

        BusinessSetting::put('review_auto_approve', '1');
        $this->postJson('/api/v1/reviews', ['name' => 'Rahim', 'rating' => 4, 'comment' => 'Fast delivery, good service'])
            ->assertCreated()->assertJsonPath('status', 'approved');
        $review = Review::query()->where('status', 'approved')->firstOrFail();
        $this->getJson('/api/v1/reviews')->assertJsonCount(1, 'data');

        $this->postJson("/api/v1/reviews/{$review->id}/helpful")->assertJsonPath('helpful', 1);
    }

    public function test_product_reviews_only_from_buyers_of_delivered_orders(): void
    {
        BusinessSetting::put('review_auto_approve', '1');
        $review = ['product_id' => $this->product->id, 'name' => 'Sadia', 'rating' => 5, 'comment' => 'Colour stays all day, loved it'];

        // no order at all: product reviews need an order number + phone
        $this->postJson('/api/v1/reviews', $review)->assertStatus(422)->assertJsonValidationErrors(['order_code', 'phone']);

        $this->order()->assertCreated();
        $order = Order::query()->firstOrFail();
        $verify = fn (array $extra = []) => $this->postJson('/api/v1/reviews/verify', ['product_id' => $this->product->id, 'order_code' => (string) $order->code, 'phone' => '01712345678', ...$extra]);

        $verify(['phone' => '01800000000'])->assertStatus(422)->assertJsonValidationErrors('order_code'); // wrong phone
        $this->withHeader('X-Locale', 'bn')->postJson('/api/v1/reviews/verify', ['product_id' => $this->product->id, 'order_code' => '999', 'phone' => '01712345678'])
            ->assertStatus(422)->assertJsonPath('errors.order_code.0', 'এই অর্ডার নম্বর ও ফোন নম্বরে কোনো অর্ডার পাওয়া যায়নি।');
        $this->flushHeaders();
        $verify()->assertStatus(422)->assertJsonPath('errors.order_code.0', 'You can review once the order has been delivered.');

        $order->update(['delivery_status' => 'delivered']);
        $other = app(ProductService::class)->save(new Product, [
            'name' => 'Kajal', 'category_ids' => [Category::query()->value('id')], 'unit_price' => 100,
            'qty' => 5, 'min_qty' => 1, 'payment_policy' => 'full_cod', 'partial_payment_type' => 'flat', 'published' => true,
        ]);
        $verify(['product_id' => $other->id])->assertStatus(422)->assertJsonPath('errors.order_code.0', 'This product is not in that order.');
        $verify(['order_code' => '#'.$order->code])->assertOk()->assertJsonPath('name', 'Sadia')->assertJsonPath('order', $order->code);

        $this->postJson('/api/v1/reviews', [...$review, 'order_code' => (string) $order->code, 'phone' => '01712345678'])
            ->assertCreated()->assertJsonPath('status', 'approved');
        $saved = Review::query()->where('product_id', $this->product->id)->firstOrFail();
        $this->assertTrue($saved->verified);
        $this->assertSame($order->id, $saved->order_id);

        // one review per product per order
        $verify()->assertStatus(422)->assertJsonPath('errors.order_code.0', 'You have already reviewed this product for this order.');
        $this->getJson('/api/v1/reviews?product_id='.$this->product->id)->assertJsonPath('data.0.verified', true);

        // the product page lists it and tells search engines about the rating
        $this->get('/product/'.$this->product->slug)->assertOk()
            ->assertSee('Colour stays all day, loved it')
            ->assertSee('✔ যাচাইকৃত ক্রেতা')
            ->assertSee('"aggregateRating":{"@type":"AggregateRating","ratingValue":5', false);
    }
}
