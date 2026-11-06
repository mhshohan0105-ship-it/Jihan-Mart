<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Color;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/** Phase 2: categories, products (simple, variants, tiers), collections, CSV import/export, reviews. */
class CatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->actingAs(User::factory()->create(['type' => User::TYPE_ADMIN]));
    }

    private function productPayload(array $extra = []): array
    {
        $cat = Category::query()->create(['name' => 'Cosmetics']);

        return [
            'name' => 'Matte Lipstick', 'category_ids' => [$cat->id], 'min_qty' => 1,
            'unit_price' => 250, 'discount' => 20, 'discount_type' => 'percent', 'qty' => 30,
            'payment_policy' => 'full_cod', 'partial_payment_type' => 'flat', 'button' => 'publish', ...$extra,
        ];
    }

    public function test_category_tree_and_slug(): void
    {
        $this->post('/admin/categories', ['name' => 'Jewellery'])->assertRedirect();
        $parent = Category::query()->firstWhere('name', 'Jewellery');
        $this->post('/admin/categories', ['name' => 'Rings', 'parent_id' => $parent->id])->assertRedirect();

        $child = Category::query()->firstWhere('name', 'Rings');
        $this->assertSame(1, $child->level);
        $this->assertSame('rings', $child->slug);
        $this->assertSame([$parent->id, $child->id], $parent->descendantIds());

        // deleting the parent moves the child up
        $this->deleteJson("/admin/categories/{$parent->id}")->assertOk();
        $this->assertNull($child->fresh()->parent_id);
    }

    public function test_simple_product_with_tiers_and_discount(): void
    {
        $this->post('/admin/products', $this->productPayload([
            'tiers' => [['min_qty' => 3, 'price' => 180]],
        ]))->assertRedirect();

        $p = Product::query()->firstOrFail();
        $this->assertTrue($p->published);
        $this->assertSame(30, $p->current_stock);
        $this->assertSame(200.0, $p->finalPrice());
        $this->assertCount(1, $p->tiers);
        $this->assertNotEmpty($p->barcode);
        $this->get("/admin/products/{$p->id}/edit")->assertOk()->assertSee('Matte Lipstick');
    }

    public function test_variant_product_builds_stock_rows(): void
    {
        $red = Color::query()->create(['name' => 'Red', 'code' => '#ff0000']);
        $blue = Color::query()->create(['name' => 'Blue', 'code' => '#0000ff']);
        $size = Attribute::query()->create(['name' => 'Size']);

        $this->post('/admin/products', $this->productPayload([
            'colors' => [$red->id, $blue->id],
            'choice_options' => [['attribute_id' => $size->id, 'values' => ['S', 'M']]],
            'stocks' => ['Red-S' => ['price' => 260, 'qty' => 5], 'Blue-M' => ['price' => 270, 'qty' => 7]],
        ]))->assertRedirect();

        $p = Product::query()->firstOrFail();
        $this->assertTrue($p->variant_product);
        $this->assertEqualsCanonicalizing(['Red-S', 'Red-M', 'Blue-S', 'Blue-M'], $p->stocks->pluck('variant')->all());
        $this->assertSame(12, $p->current_stock);
    }

    public function test_unpublish_toggle_duplicate_and_collection(): void
    {
        $this->post('/admin/products', $this->productPayload(['button' => 'unpublish']));
        $p = Product::query()->firstOrFail();
        $this->assertFalse($p->published);

        $this->postJson("/admin/toggle/products/{$p->id}", ['field' => 'featured', 'value' => 1])->assertOk();
        $this->assertTrue($p->fresh()->featured);
        $this->postJson("/admin/toggle/products/{$p->id}", ['field' => 'name', 'value' => 1])->assertUnprocessable();

        $c = Collection::query()->create(['name' => 'Free Delivery']);
        $this->postJson("/admin/collections/{$c->id}/products/{$p->id}", ['value' => 1])->assertOk();
        $this->assertCount(1, $c->products);

        $this->post("/admin/products/{$p->id}/duplicate")->assertRedirect();
        $this->assertSame(2, Product::query()->count());
    }

    public function test_percent_discount_over_100_is_rejected(): void
    {
        $this->post('/admin/products', $this->productPayload(['discount' => 150]))->assertSessionHasErrors('discount');
    }

    public function test_csv_import_and_export(): void
    {
        $cat = Category::query()->create(['name' => 'Bags']);
        $csv = "\xEF\xBB\xBFname,category_ids,unit_price,qty,discount_type\nLeather Bag,{$cat->id},1290,4,flat\nBroken row,999,abc,1,flat\n";
        $file = UploadedFile::fake()->createWithContent('products.csv', $csv);

        $this->post('/admin/product-bulk-upload', ['file' => $file])->assertSessionHas('import_errors');
        $this->assertSame(1, Product::query()->count());
        $this->assertSame(4, Product::query()->first()->current_stock);

        $export = $this->get('/admin/product-bulk-export')->assertOk()->streamedContent();
        $this->assertStringContainsString('Leather Bag', $export);
    }

    public function test_approved_reviews_update_product_rating(): void
    {
        $this->post('/admin/products', $this->productPayload());
        $p = Product::query()->firstOrFail();
        $r = Review::query()->create(['product_id' => $p->id, 'name' => 'Sadia', 'rating' => 4, 'comment' => 'Nice colour']);
        $this->assertSame(0, $p->fresh()->review_count); // pending does not count

        $this->postJson("/admin/reviews/{$r->id}/status", ['status' => 'approved'])->assertOk();
        $this->assertSame(1, $p->fresh()->review_count);
        $this->assertEquals(4.0, $p->fresh()->rating);
        $this->get('/admin/reviews')->assertOk()->assertSee('Nice colour');
    }

    public function test_staff_without_permission_cannot_edit_products(): void
    {
        $staff = User::factory()->create(['type' => User::TYPE_STAFF]);
        $staff->givePermissionTo('view_products');
        $this->actingAs($staff)->get('/admin/products/all')->assertOk();
        $this->get('/admin/products/create')->assertForbidden();
    }
}
