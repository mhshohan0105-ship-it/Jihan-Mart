<?php

namespace Tests\Feature;

use App\Models\AbandonedCart;
use App\Models\BusinessSetting;
use App\Models\Category;
use App\Models\City;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductService;
use Database\Seeders\LocationSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Phase 3: shipping cost, POS orders, stock, statuses, calls, invoices, abandoned carts. */
class OrderTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private City $city;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PermissionSeeder::class, LocationSeeder::class]);
        $this->actingAs(User::factory()->create(['type' => User::TYPE_ADMIN]));

        $cat = Category::query()->create(['name' => 'Bags']);
        $this->product = app(ProductService::class)->save(new Product, [
            'name' => 'Leather Bag', 'category_ids' => [$cat->id], 'unit_price' => 1000, 'discount' => 0, 'discount_type' => 'flat',
            'qty' => 5, 'min_qty' => 1, 'payment_policy' => 'full_cod', 'partial_payment_type' => 'flat', 'published' => true,
        ]);
        $this->city = City::query()->where('name', 'Mirpur')->firstOrFail(); // ঢাকার ভিতরে, ৳60
    }

    private function createOrder(array $extra = []): Order
    {
        $this->postJson('/admin/orders', [
            'name' => 'Rahim', 'phone' => '+880 1712-345678', 'address' => 'House 1, Road 2',
            'state_id' => $this->city->state_id, 'city_id' => $this->city->id,
            'items' => [['product_id' => $this->product->id, 'variant' => '', 'quantity' => 2]],
            ...$extra,
        ])->assertOk();

        return Order::query()->latest('id')->firstOrFail();
    }

    public function test_pos_order_deducts_stock_and_calculates_totals(): void
    {
        $order = $this->createOrder(['advance_amount' => 500, 'advance_method' => 'bkash']);

        $this->assertSame(1001, (int) $order->code);
        $this->assertSame('01712345678', $order->phone);
        $this->assertEquals(2000, $order->subtotal);
        $this->assertEquals(60, $order->shipping_cost);
        $this->assertEquals(2060, $order->grand_total);
        $this->assertEquals(1560, $order->due);
        $this->assertSame('partial_paid', $order->payment_status);
        $this->assertSame(3, $this->product->fresh()->current_stock);
        $this->assertSame(1, User::query()->where('type', 'customer')->where('phone', '01712345678')->count());
        $this->assertSame('order_created', $order->activities()->first()->action_type);
    }

    public function test_free_shipping_over_threshold(): void
    {
        BusinessSetting::put('free_shipping_enabled', '1');
        BusinessSetting::put('free_shipping_min_amount', '1500');

        $this->assertEquals(0, $this->createOrder()->shipping_cost);
    }

    public function test_not_enough_stock_is_refused(): void
    {
        $this->postJson('/admin/orders', [
            'name' => 'Rahim', 'phone' => '01712345678', 'address' => 'x',
            'items' => [['product_id' => $this->product->id, 'quantity' => 9]],
        ])->assertUnprocessable()->assertJsonValidationErrors('items');
        $this->assertSame(5, $this->product->fresh()->current_stock);
    }

    public function test_cancel_restores_stock_and_reconfirm_takes_it_again(): void
    {
        $order = $this->createOrder();

        $this->postJson('/admin/orders/update_delivery_status', ['order_id' => $order->id, 'status' => 'cancelled'])->assertOk();
        $this->assertSame(5, $this->product->fresh()->current_stock);

        $this->postJson('/admin/orders/update_delivery_status', ['order_id' => $order->id, 'status' => 'confirmed'])->assertOk();
        $this->assertSame(3, $this->product->fresh()->current_stock);

        $log = $order->activities()->where('action_type', 'status_change')->latest('id')->first();
        $this->assertSame('Cancel', $log->old_value);
        $this->assertSame('Confirmed', $log->new_value);
    }

    public function test_edit_order_rebalances_stock(): void
    {
        $order = $this->createOrder();
        $this->putJson("/admin/orders/{$order->id}", [
            'name' => 'Rahim', 'phone' => '01712345678', 'address' => 'x',
            'items' => [['product_id' => $this->product->id, 'quantity' => 4]],
        ])->assertOk();

        $this->assertSame(1, $this->product->fresh()->current_stock);
        $this->assertEquals(4000, $order->fresh()->subtotal);
    }

    public function test_calls_notes_and_pages(): void
    {
        $order = $this->createOrder();

        $this->postJson('/admin/orders/log-call', ['order_id' => $order->id])->assertJson(['total_calls' => 1]);
        $this->postJson('/admin/orders/add-call-note', ['order_id' => $order->id, 'note' => 'Will call later'])->assertJson(['total_calls' => 2]);
        $this->postJson('/admin/orders/update_admin_note', ['order_id' => $order->id, 'admin_note' => 'VIP'])->assertOk();
        $this->getJson("/admin/orders/{$order->id}/calls")->assertJsonPath('total', 2);
        $this->getJson("/admin/orders/{$order->id}/activities")->assertOk();

        $this->get('/admin/all_orders')->assertOk()->assertSee('#1001');
        $this->get('/admin/all_orders/pending?search=01712345678')->assertOk()->assertSee('Rahim');
        $this->get('/admin/all_orders/delivered')->assertOk()->assertDontSee('#1001');
        $this->get('/admin/orders/'.$order->encryptedId())->assertOk()->assertSee('VIP');
        $this->get('/admin/invoice?id[]='.$order->id)->assertOk()->assertSee('Leather Bag');

        $this->postJson('/admin/orders/update_delivery_status', ['order_id' => $order->id, 'status' => 'confirmed']);
        $this->get('/admin/orders-packaging-checklist')->assertOk()->assertSee('#1001');
        $this->get('/admin/orders/create')->assertOk();
        $this->get("/admin/pos-order-edit/{$order->id}")->assertOk();
        $this->getJson('/admin/orders/pos-products?search=Leather')->assertJsonPath('data.0.name', 'Leather Bag');
    }

    public function test_abandoned_cart_converts_to_order(): void
    {
        $cart = AbandonedCart::query()->create([
            'name' => 'Karim', 'phone' => '01812345678', 'address' => 'Chattogram', 'city_id' => $this->city->id,
            'items' => [['product_id' => $this->product->id, 'variant' => '', 'quantity' => 1, 'name' => 'Leather Bag']],
        ]);

        $this->get('/admin/abandoned-carts')->assertOk()->assertSee('Karim');
        $this->postJson("/admin/abandoned-carts/{$cart->id}/convert")->assertOk();
        $this->assertSame('converted', $cart->fresh()->status);
        $this->assertSame('abandoned', Order::query()->first()->source);
    }

    public function test_shipping_pages_and_area_endpoint(): void
    {
        $this->get('/admin/shipping_configuration')->assertOk();
        $this->get('/admin/states')->assertOk()->assertSee('ঢাকার ভিতরে');
        $this->get('/admin/cities?state_id='.$this->city->state_id)->assertOk()->assertSee('Mirpur');
        $this->postJson('/admin/get-cities', ['state_id' => $this->city->state_id])->assertJsonFragment(['name' => 'Mirpur']);
        $this->get('/admin/customers')->assertOk();
    }

    public function test_staff_without_delete_permission_cannot_delete_orders(): void
    {
        $order = $this->createOrder();
        $staff = User::factory()->create(['type' => User::TYPE_STAFF]);
        $staff->givePermissionTo(['view_orders']);

        $this->actingAs($staff)->get('/admin/all_orders')->assertOk()->assertDontSee('Delete selection');
        $this->deleteJson("/admin/orders/{$order->id}")->assertForbidden();
    }
}
