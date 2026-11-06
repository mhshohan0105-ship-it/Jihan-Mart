<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\SetupController;
use App\Models\BusinessSetting;
use App\Models\Category;
use App\Models\City;
use App\Models\Order;
use App\Models\Product;
use App\Models\Tax;
use App\Models\User;
use App\Services\ProductService;
use Database\Seeders\LocationSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Phase 8: Setup & Configurations and Staffs. */
class ConfigurationTest extends TestCase
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
            'name' => 'Toner', 'category_ids' => [$cat->id], 'unit_price' => 200,
            'qty' => 20, 'min_qty' => 1, 'payment_policy' => 'full_cod', 'partial_payment_type' => 'flat', 'published' => true,
        ]);
        $this->city = City::query()->where('name', 'Mirpur')->firstOrFail();
    }

    private function order(array $extra = [])
    {
        return $this->postJson('/api/v1/orders', [
            'name' => 'Nila', 'phone' => '01512345678', 'address' => 'Mirpur 6', 'state_id' => $this->city->state_id, 'city_id' => $this->city->id,
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]], ...$extra,
        ]);
    }

    public function test_setup_pages_load(): void
    {
        foreach (['features-activation', 'languages', 'languages/bn/translations', 'tax', 'staffs', 'staffs/create', 'roles', 'roles/create', 'permissions'] as $uri) {
            $this->get("/admin/$uri")->assertOk();
        }
        foreach (array_keys(SetupController::PAGES) as $page) {
            $this->get("/admin/setup/$page")->assertOk();
        }
        $this->get('/admin/setup/nope')->assertNotFound();
    }

    public function test_payment_methods_and_minimum_order(): void
    {
        $this->postJson('/admin/features-activation', ['field' => 'nagad_payment', 'value' => 0])->assertOk();
        $this->postJson('/admin/features-activation', ['field' => 'not_a_feature', 'value' => 1])->assertStatus(422);
        $this->assertSame(['cod', 'bkash', 'rocket'], $this->getJson('/api/v1/settings')->json('payments'));
        $this->order(['payment_method' => 'nagad', 'transaction_id' => 'X1'])->assertStatus(422)->assertJsonValidationErrors('payment_method');

        BusinessSetting::put('minimum_order_amount_check', '1');
        BusinessSetting::put('minimum_order_amount', '500');
        $this->order()->assertStatus(422)->assertJsonValidationErrors('items');
        $this->order(['items' => [['product_id' => $this->product->id, 'quantity' => 3]]])->assertCreated();
        $this->assertSame(1, Order::query()->count());
    }

    public function test_vat_is_added_to_the_price(): void
    {
        $this->post('/admin/tax', ['name' => 'VAT'])->assertRedirect('/admin/tax');
        $tax = Tax::query()->firstOrFail();
        $this->put("/admin/products/{$this->product->id}", [
            'name' => 'Toner', 'category_ids' => [Category::query()->value('id')], 'unit_price' => 200, 'qty' => 20, 'min_qty' => 1,
            'discount' => 0, 'discount_type' => 'flat', 'payment_policy' => 'full_cod', 'partial_payment_type' => 'flat', 'published' => 1,
            'taxes' => [$tax->id => ['tax' => 5, 'tax_type' => 'percent']],
        ])->assertSessionHasNoErrors();
        $this->assertSame(210.0, $this->product->fresh()->finalPrice());

        $this->postJson("/admin/toggle/taxes/{$tax->id}", ['field' => 'status', 'value' => 0])->assertOk();
        Tax::forgetMap();
        $this->assertSame(200.0, $this->product->fresh()->finalPrice());
    }

    public function test_maintenance_mode_blocks_visitors_but_not_staff(): void
    {
        BusinessSetting::put('maintenance_mode', '1');
        $this->get('/')->assertOk(); // logged in admin still sees the shop
        $this->get('/admin')->assertOk();

        auth()->logout();
        $this->get('/')->assertStatus(503)->assertSee('শীঘ্রই ফিরছি');
        $this->order()->assertStatus(503);
        $this->get('/login')->assertOk();
    }

    public function test_staff_and_roles(): void
    {
        $this->post('/admin/roles', ['name' => 'Packer', 'permissions' => ['view_orders', 'print_invoices']])->assertRedirect('/admin/roles');
        $this->post('/admin/staffs', ['name' => 'Rafi', 'email' => 'rafi@example.com', 'password' => 'secret123', 'role' => 'Packer'])->assertRedirect('/admin/staffs');

        $staff = User::query()->where('email', 'rafi@example.com')->firstOrFail();
        $this->assertSame(User::TYPE_STAFF, $staff->type);
        $this->assertTrue($staff->can('print_invoices'));
        $this->assertFalse($staff->can('delete_orders'));

        // a role in use cannot be deleted
        $this->deleteJson('/admin/roles/'.Role::findByName('Packer')->id)->assertStatus(422);

        $this->actingAs($staff)->get('/admin/staffs')->assertForbidden();
        $this->actingAs($staff)->get('/admin/setup/smtp-settings')->assertForbidden();
    }

    public function test_env_keys_need_the_matching_permission(): void
    {
        $staff = User::factory()->create(['type' => User::TYPE_STAFF]);
        $staff->givePermissionTo('manage_google');
        // never touch the real .env: point the app at a scratch copy
        $dir = storage_path('framework/testing/env');
        File::ensureDirectoryExists($dir);
        File::put($dir.'/.env', 'APP_NAME=Test
');
        $this->app->useEnvironmentPath($dir);
        try {
            $this->actingAs($staff)->post('/admin/env-key-update', [
                'types' => ['RECAPTCHA_SITE_KEY', 'MAIL_HOST'], 'RECAPTCHA_SITE_KEY' => 'site-key-123', 'MAIL_HOST' => 'evil.example.com',
            ])->assertRedirect();
            $saved = File::get($dir.'/.env');
            $this->assertTrue(str_contains($saved, 'site-key-123'), 'reCAPTCHA key was not saved');
            $this->assertFalse(str_contains($saved, 'evil.example.com'), 'MAIL_HOST was saved without manage_smtp');
        } finally {
            File::deleteDirectory($dir);
        }
    }

    public function test_translation_editor_saves_bangla(): void
    {
        $path = lang_path('bn.json');
        $backup = File::get($path);
        try {
            $this->post('/admin/languages/bn/translations', ['keys' => ['Test Phrase Xyz'], 'values' => ['পরীক্ষা']])->assertRedirect();
            $this->assertSame('পরীক্ষা', json_decode(File::get($path), true)['Test Phrase Xyz']);
        } finally {
            File::put($path, $backup);
        }
    }
}
