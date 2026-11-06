<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\City;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use App\Services\ProductService;
use Database\Seeders\LocationSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Phase 4: Steadfast/Pathao booking, webhook, fraud check. Courier APIs are faked. */
class CourierTest extends TestCase
{
    use RefreshDatabase;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PermissionSeeder::class, LocationSeeder::class]);
        $this->actingAs(User::factory()->create(['type' => User::TYPE_ADMIN]));
        config([
            'couriers.steadfast.api_key' => 'test-key', 'couriers.steadfast.secret_key' => 'test-secret', 'couriers.steadfast.webhook_secret' => 'hook-token',
            'couriers.pathao.client_id' => 'id', 'couriers.pathao.client_secret' => 's', 'couriers.pathao.username' => 'u', 'couriers.pathao.password' => 'p', 'couriers.pathao.store_id' => 7,
        ]);

        $cat = Category::query()->create(['name' => 'Bags']);
        $product = app(ProductService::class)->save(new Product, [
            'name' => 'Leather Bag', 'category_ids' => [$cat->id], 'unit_price' => 1000, 'discount' => 0, 'discount_type' => 'flat',
            'qty' => 5, 'min_qty' => 1, 'payment_policy' => 'full_cod', 'partial_payment_type' => 'flat', 'published' => true,
        ]);
        $city = City::query()->where('name', 'Mirpur')->firstOrFail();
        $this->order = app(OrderService::class)->create([
            'name' => 'Rahim', 'phone' => '01712345678', 'address' => 'House 1', 'state_id' => $city->state_id, 'city_id' => $city->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);
    }

    public function test_send_to_steadfast_saves_consignment_and_blocks_resend(): void
    {
        Http::fake(['portal.packzy.com/api/v1/create_order' => Http::response(['status' => 200, 'consignment' => ['consignment_id' => 555, 'tracking_code' => 'TRK1', 'status' => 'in_review']])]);

        $this->postJson('/admin/create-order-courier', ['ids' => [$this->order->id], 'courier' => 'steadfast'])->assertOk()->assertJsonPath('success', true);

        $order = $this->order->fresh();
        $this->assertSame('steadfast', $order->courier);
        $this->assertSame('555', $order->consignment_id);
        $this->assertSame('TRK1', $order->tracking_code);
        $this->assertSame('picked_up', $order->delivery_status);
        $this->assertTrue($order->activities()->where('action_type', 'courier_sent')->exists());
        Http::assertSent(fn ($r) => $r->hasHeader('api-key', 'test-key') && $r['cod_amount'] == 1060 && $r['invoice'] === '1001');

        $this->postJson('/admin/create-order-courier', ['ids' => [$this->order->id]])->assertStatus(422);
    }

    public function test_pathao_needs_area_mapping(): void
    {
        Http::fake([
            '*/issue-token' => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
            '*/aladdin/api/v1/orders' => Http::response(['data' => ['consignment_id' => 'DP123', 'order_status' => 'Pending']]),
        ]);

        $this->postJson('/admin/create-order-courier', ['ids' => [$this->order->id], 'courier' => 'pathao'])->assertStatus(422);

        $this->order->city->update(['pathao_city_id' => 1, 'pathao_zone_id' => 2]);
        $this->postJson('/admin/create-order-courier', ['ids' => [$this->order->id], 'courier' => 'pathao'])->assertOk();
        $this->assertSame('DP123', $this->order->fresh()->consignment_id);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/orders') && $r['recipient_city'] === 1 && $r['store_id'] === 7);
    }

    public function test_steadfast_webhook_updates_status(): void
    {
        $this->order->update(['courier' => 'steadfast', 'consignment_id' => '555']);

        $this->postJson('/api/v1/webhooks/steadfast', ['consignment_id' => 555, 'status' => 'delivered'])->assertUnauthorized();
        $this->withToken('hook-token')->postJson('/api/v1/webhooks/steadfast', ['consignment_id' => 555, 'status' => 'delivered', 'tracking_message' => 'Delivered to customer'])
            ->assertOk()->assertJsonPath('status', 'success');

        $this->assertSame('delivered', $this->order->fresh()->delivery_status);
    }

    public function test_fraud_check_combines_steadfast_and_store_history(): void
    {
        Http::fake(['portal.packzy.com/api/v1/fraud_check/score/*' => Http::response(['total_parcels' => 10, 'total_delivered' => 9, 'total_cancelled' => 1])]);

        $this->getJson('/admin/orders-fraud/check?phone=01712345678&force_refresh=1')->assertOk()
            ->assertJsonPath('data.steadfast.total', 10)
            ->assertJsonPath('data.overall.level', 'safe');

        // the list badges read the cache, no new courier call
        Http::fake();
        $this->postJson('/admin/orders-fraud/batch', ['phones' => ['01712345678']])->assertOk()->assertJsonPath('data.01712345678.rate', 90);
        Http::assertNothingSent();
    }

    public function test_courier_settings_page_and_order_pages(): void
    {
        $this->get('/admin/courier-settings')->assertOk()->assertSee('Steadfast')->assertSee(route('api.webhooks.steadfast'));
        $this->get('/admin/all_orders')->assertOk()->assertSee('Bulk send courier');
        $this->get('/admin/orders/'.$this->order->encryptedId())->assertOk()->assertSee('Send Courier');
    }
}
