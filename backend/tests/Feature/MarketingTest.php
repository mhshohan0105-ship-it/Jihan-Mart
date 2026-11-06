<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\City;
use App\Models\Coupon;
use App\Models\FlashDeal;
use App\Models\Order;
use App\Models\Product;
use App\Models\Subscriber;
use App\Models\User;
use App\Services\ProductService;
use Database\Seeders\LocationSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Phase 6: flash deals, coupons, subscribers, newsletter and the product feed. */
class MarketingTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private City $city;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PermissionSeeder::class, LocationSeeder::class]);
        $this->actingAs(User::factory()->create(['type' => User::TYPE_ADMIN]));
        $cat = Category::query()->create(['name' => 'Beauty']);
        $this->product = app(ProductService::class)->save(new Product, [
            'name' => 'Face Wash', 'category_ids' => [$cat->id], 'unit_price' => 500, 'discount' => 50, 'discount_type' => 'flat',
            'qty' => 20, 'min_qty' => 1, 'payment_policy' => 'full_cod', 'partial_payment_type' => 'flat', 'published' => true,
        ]);
        $this->city = City::query()->where('name', 'Mirpur')->firstOrFail();
    }

    private function order(array $extra = [])
    {
        return $this->postJson('/api/v1/orders', [
            'name' => 'Rumi', 'phone' => '01812345678', 'address' => 'Mirpur 2',
            'state_id' => $this->city->state_id, 'city_id' => $this->city->id,
            'items' => [['product_id' => $this->product->id, 'quantity' => 2]],
            ...$extra,
        ]);
    }

    public function test_admin_pages_load(): void
    {
        foreach (['flash-deals', 'flash-deals/create', 'coupons', 'coupons/create', 'subscribers', 'newsletter'] as $uri) {
            $this->get("/admin/$uri")->assertOk();
        }
    }

    public function test_running_flash_deal_replaces_product_discount_and_reaches_storefront(): void
    {
        $this->post('/admin/flash-deals', [
            'title' => 'Eid Sale', 'text_color' => 'white', 'background_color' => '#f47c20', 'status' => 1, 'featured' => 1,
            'start_date' => now()->subHour()->format('Y-m-d H:i'), 'end_date' => now()->addDay()->format('Y-m-d H:i'),
            'products' => [$this->product->id => ['discount' => 20, 'discount_type' => 'percent']],
        ])->assertRedirect('/admin/flash-deals');

        $this->assertSame(400.0, $this->product->fresh()->finalPrice());
        $this->getJson('/api/v1/catalog')->assertOk()
            ->assertJsonPath('flash.title', 'Eid Sale')
            ->assertJsonPath('flash.ids.0', $this->product->id)
            ->assertJsonPath('products.0.price', 400);

        // switching it off brings the normal discount back
        $deal = FlashDeal::query()->firstOrFail();
        $this->postJson("/admin/toggle/flash_deals/{$deal->id}", ['field' => 'status', 'value' => 0])->assertOk();
        FlashDeal::forgetMap();
        $this->assertSame(450.0, $this->product->fresh()->finalPrice());
        $this->getJson('/api/v1/catalog')->assertJsonPath('flash', null);
    }

    public function test_cart_coupon_on_storefront_order(): void
    {
        $this->post('/admin/coupons', [
            'type' => 'cart', 'code' => 'eid100', 'discount' => 10, 'discount_type' => 'percent', 'min_buy' => 500,
            'max_discount' => 60, 'usage_limit_per_user' => 1, 'status' => 1,
        ])->assertRedirect('/admin/coupons');

        $this->postJson('/api/v1/coupons/validate', ['code' => 'EID100', 'items' => [['product_id' => $this->product->id, 'quantity' => 2]]])
            ->assertOk()->assertJsonPath('discount', 60)->assertJsonPath('subtotal', 900);
        $this->postJson('/api/v1/coupons/validate', ['code' => 'EID100', 'items' => [['product_id' => $this->product->id, 'quantity' => 1]]])
            ->assertStatus(422)->assertJsonValidationErrors('coupon_code');

        $this->order(['coupon_code' => 'eid100'])->assertCreated();
        $order = Order::query()->firstOrFail();
        $this->assertSame('EID100', $order->coupon_code);
        $this->assertEquals(60, $order->coupon_discount);
        $this->assertEquals(900 + $order->shipping_cost - 60, $order->grand_total);
        $this->assertSame(1, Coupon::query()->first()->usages()->count());

        // one use per customer
        $this->order(['coupon_code' => 'EID100'])->assertStatus(422)->assertJsonValidationErrors('coupon_code');
    }

    public function test_product_coupon_only_discounts_its_products(): void
    {
        Coupon::query()->create(['type' => 'product', 'code' => 'FACE50', 'discount' => 50, 'discount_type' => 'flat', 'product_ids' => [$this->product->id], 'status' => true]);
        Coupon::query()->create(['type' => 'product', 'code' => 'OTHER', 'discount' => 50, 'discount_type' => 'flat', 'product_ids' => [999], 'status' => true]);
        Coupon::query()->create(['type' => 'cart', 'code' => 'OLD', 'discount' => 50, 'discount_type' => 'flat', 'end_date' => now()->subDay(), 'status' => true]);

        $items = [['product_id' => $this->product->id, 'quantity' => 1]];
        $this->postJson('/api/v1/coupons/validate', ['code' => 'face50', 'items' => $items])->assertOk()->assertJsonPath('discount', 50);
        $this->postJson('/api/v1/coupons/validate', ['code' => 'OTHER', 'items' => $items])->assertStatus(422);
        $this->postJson('/api/v1/coupons/validate', ['code' => 'OLD', 'items' => $items])->assertStatus(422);
    }

    public function test_subscribe_newsletter_and_feed(): void
    {
        $this->postJson('/api/v1/subscribers', ['contact' => 'Buyer@Example.com'])->assertCreated();
        $this->postJson('/api/v1/subscribers', ['contact' => 'buyer@example.com'])->assertCreated(); // no duplicate
        $this->postJson('/api/v1/subscribers', ['contact' => '01912345678'])->assertCreated();
        $this->postJson('/api/v1/subscribers', ['contact' => 'hello'])->assertStatus(422);
        $this->assertSame(2, Subscriber::query()->count());

        $this->post('/admin/newsletter', ['subject' => 'Eid offer', 'content' => '<p>Hi</p>', 'to_subscribers' => 1])
            ->assertSessionHas('success');

        $csv = $this->get('/feeds/products.csv')->assertOk()->streamedContent();
        $this->assertStringContainsString('id,title,description,availability', $csv);
        $this->assertStringContainsString('"in stock",new,"500.00 BDT","450.00 BDT"', $csv);
        $this->get('/admin/product-feed.csv')->assertOk();
    }
}
