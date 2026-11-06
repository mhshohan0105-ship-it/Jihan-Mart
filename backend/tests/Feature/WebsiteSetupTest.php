<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\City;
use App\Models\LandingPage;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\Upload;
use App\Models\User;
use App\Services\ProductService;
use Database\Seeders\LocationSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Phase 7: header / footer / appearance settings, pages, home page and landing pages. */
class WebsiteSetupTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PermissionSeeder::class, LocationSeeder::class]);
        $this->actingAs(User::factory()->create(['type' => User::TYPE_ADMIN]));
        $cat = Category::query()->create(['name' => 'Beauty']);
        $this->product = app(ProductService::class)->save(new Product, [
            'name' => 'Serum', 'category_ids' => [$cat->id], 'unit_price' => 700,
            'qty' => 20, 'min_qty' => 1, 'payment_policy' => 'full_cod', 'partial_payment_type' => 'flat', 'published' => true,
        ]);
    }

    public function test_admin_pages_load(): void
    {
        foreach (['website/header', 'website/footer', 'website/appearance', 'website/pages', 'website/pages/create', 'landing-home-page', 'landing-home-page/create', 'landing-page-order-index'] as $uri) {
            $this->get("/admin/$uri")->assertOk();
        }
        $this->assertSame(count(Page::DEFAULTS), Page::query()->count()); // defaults created on first visit
        $this->get('/admin/website/pages/'.Page::query()->where('slug', 'home')->value('id').'/edit')->assertOk()->assertSee('Home Slider');
    }

    public function test_header_footer_appearance_reach_storefront(): void
    {
        $upload = Upload::query()->create(['file_original_name' => 'b', 'file_name' => 'uploads/all/b.webp', 'extension' => 'webp', 'type' => 'image', 'file_size' => 1]);

        $this->post('/admin/business-settings/update', [
            'types' => ['header_menu', 'topbar_announcements', 'about_us_description', 'widget_one_links', 'home_slider', 'base_color', 'meta_title', 'header_script', 'show_cookies_agreement', 'cookies_agreement_text'],
            'header_menu' => [['label' => 'Offers', 'link' => '#flash'], ['label' => '', 'link' => '']],
            'topbar_announcements' => [['text' => 'Eid sale is live']],
            'about_us_description' => 'Best shop in Dhaka',
            'widget_one_links' => [['label' => 'Blog', 'link' => 'https://blog.example.com']],
            'home_slider' => [['image' => $upload->id, 'link' => '#shop']],
            'base_color' => '#123456',
            'meta_title' => 'Jihan Mart Online Shop',
            'header_script' => '<script>window.pixel=1</script>',
            'show_cookies_agreement' => '1',
            'cookies_agreement_text' => 'We use cookies',
        ])->assertRedirect();

        $settings = $this->getJson('/api/v1/settings')->assertOk()->json();
        $this->assertSame([['label' => 'Offers', 'link' => '#flash']], $settings['header']['menu']); // empty rows dropped
        $this->assertSame(['Eid sale is live'], $settings['header']['announcements']);
        $this->assertSame('Best shop in Dhaka', $settings['footer']['about']);
        $this->assertSame('#shop', $settings['home']['slider'][0]['link']);
        $this->assertSame('We use cookies', $settings['cookies']);

        $this->get('/')->assertOk()
            ->assertSee('<title>Jihan Mart Online Shop</title>', false)
            ->assertSee('--orange: #123456', false)
            ->assertSee('<script>window.pixel=1</script>', false);
    }

    public function test_staff_without_appearance_cannot_set_scripts(): void
    {
        $staff = User::factory()->create(['type' => User::TYPE_STAFF]);
        $staff->givePermissionTo('manage_footer');
        $this->actingAs($staff)->post('/admin/business-settings/update', [
            'types' => ['about_us_description', 'footer_script'], 'about_us_description' => 'ok', 'footer_script' => '<script>x</script>',
        ])->assertRedirect();
        $this->assertSame('ok', get_setting('about_us_description'));
        $this->assertNull(get_setting('footer_script'));
    }

    public function test_custom_page_crud_and_footer_link(): void
    {
        $this->get('/admin/website/pages'); // creates the defaults
        $this->post('/admin/website/pages', ['title' => 'About Jihan Mart', 'slug' => '', 'content' => '<p>Since 2020</p>', 'position' => 'about'])
            ->assertRedirect('/admin/website/pages');
        $page = Page::query()->where('slug', 'about-jihan-mart')->firstOrFail();

        $this->get('/page/about-jihan-mart')->assertOk()->assertSee('Since 2020', false);
        $this->getJson('/api/v1/settings')->assertJsonFragment(['label' => 'About Jihan Mart', 'link' => url('/page/about-jihan-mart')]);

        // default pages keep their slug and cannot be deleted
        $terms = Page::query()->where('slug', 'terms')->firstOrFail();
        $this->put("/admin/website/pages/{$terms->id}", ['title' => 'Terms', 'slug' => 'changed', 'content' => '<p>Rules</p>'])->assertRedirect();
        $this->assertSame('terms', $terms->fresh()->slug);
        $this->deleteJson("/admin/website/pages/{$terms->id}")->assertForbidden();
        $this->deleteJson("/admin/website/pages/{$page->id}")->assertOk();
        $this->get('/page/about-jihan-mart')->assertNotFound();
    }

    public function test_landing_page_domain_serves_its_page_and_tags_orders(): void
    {
        $this->post('/admin/landing-home-page', [
            'domain' => 'https://Offer.Example.com/', 'status' => 1, 'product_ids' => [$this->product->id],
            'contents' => [['title' => 'Why this serum', 'body' => '<p>Glow in 7 days</p>']], 'phone' => '01700000000',
        ])->assertRedirect('/admin/landing-home-page');
        $landing = LandingPage::query()->firstOrFail();
        $this->assertSame('offer.example.com', $landing->domain);

        $this->get('http://offer.example.com/')->assertOk()->assertSee('Why this serum')->assertSee('Serum')->assertDontSee('window.STORE', false);
        $this->get('http://localhost/')->assertOk()->assertSee('window.STORE', false);

        $city = City::query()->where('name', 'Mirpur')->firstOrFail();
        $order = [
            'name' => 'Lima', 'phone' => '01612345678', 'address' => 'Mirpur 1', 'state_id' => $city->state_id, 'city_id' => $city->id,
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]], 'source' => 'landing', 'landing_domain' => 'evil.com',
        ];
        $this->postJson('http://offer.example.com/api/v1/orders', $order)->assertCreated();
        $this->postJson('http://localhost/api/v1/orders', [...$order, 'phone' => '01612345679'])->assertCreated();

        $orders = Order::query()->orderBy('id')->get();
        $this->assertSame(['landing', 'offer.example.com'], [$orders[0]->source, $orders[0]->landing_domain]);
        $this->assertSame(['website', null], [$orders[1]->source, $orders[1]->landing_domain]); // not a landing host

        $this->get('/admin/landing-page-order-index')->assertOk()->assertSee('offer.example.com')->assertSee('01612345678')->assertDontSee('01612345679');
    }
}
