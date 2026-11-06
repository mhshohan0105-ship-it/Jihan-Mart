<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use App\Services\ProductService;
use Database\Seeders\LocationSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Phase 5: dashboard numbers and reports (cancelled/returned never count as revenue). */
class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PermissionSeeder::class, LocationSeeder::class]);
        $this->actingAs(User::factory()->create(['type' => User::TYPE_ADMIN]));

        $cat = Category::query()->create(['name' => 'Bags']);
        $product = app(ProductService::class)->save(new Product, [
            'name' => 'Leather Bag', 'category_ids' => [$cat->id], 'unit_price' => 1000, 'discount' => 0, 'discount_type' => 'flat',
            'qty' => 20, 'min_qty' => 1, 'payment_policy' => 'full_cod', 'partial_payment_type' => 'flat', 'published' => true,
        ]);
        $orders = app(OrderService::class);
        $make = fn (int $qty) => $orders->create(['name' => 'Rahim', 'phone' => '01712345678', 'address' => 'x', 'shipping_cost' => 100,
            'items' => [['product_id' => $product->id, 'quantity' => $qty]]]);

        $orders->changeDeliveryStatus($make(2), 'delivered');   // 2100
        $make(1);                                               // 1100 pending
        $orders->changeDeliveryStatus($make(3), 'cancelled');   // 3100, not revenue
    }

    public function test_dashboard_counts_revenue_without_cancelled_orders(): void
    {
        $this->get('/admin')->assertOk()
            ->assertSee('Total Sales Revenue')
            ->assertSee('৳3,200')       // 2100 + 1100
            ->assertSee('Category Wise Sales');
        $this->getJson('/admin/dashboard/realtime')->assertOk()->assertJsonPath('orders', 3);
    }

    public function test_date_wise_report_defaults_to_delivered_and_exports(): void
    {
        $this->get('/admin/date-wise-sale-report')->assertOk()->assertSee('৳2,000.00')->assertSee('2 Pcs');
        $this->get('/admin/date-wise-sale-report?delivery_status=sales&group_by=product')->assertOk()->assertSee('৳3,000.00');

        $csv = $this->get('/admin/date-wise-sale-report?export=csv')->assertOk()->streamedContent();
        $this->assertStringContainsString('Leather Bag', $csv);
    }

    public function test_summary_product_sale_and_stock_reports(): void
    {
        $this->get('/admin/sale-summary-report')->assertOk()->assertSee('Daily Sales Summary Breakdown')->assertSee('#1001');
        $this->get('/admin/sale-summary-report?view_mode=monthly&delivery_status=all')->assertOk()->assertSee(now()->format('F Y'));
        $this->get('/admin/in_house_sale_report')->assertOk()->assertSee('Leather Bag');
        $this->get('/admin/stock_report')->assertOk()->assertSee('17'); // 20 - 2 - 1 (cancelled stock came back)
    }

    public function test_export_needs_permission(): void
    {
        $staff = User::factory()->create(['type' => User::TYPE_STAFF]);
        $staff->givePermissionTo('view_reports');

        $this->actingAs($staff)->get('/admin/stock_report')->assertOk()->assertDontSee('Export to Excel');
        $this->get('/admin/stock_report?export=csv')->assertForbidden();
    }
}
