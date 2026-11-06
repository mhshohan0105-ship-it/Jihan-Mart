<?php

namespace Tests\Feature;

use App\Models\BusinessSetting;
use App\Models\Upload;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Phase 1: login, admin access, permissions, settings and the media library. */
class AdminFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
    }

    private function admin(): User
    {
        return User::factory()->create(['type' => User::TYPE_ADMIN]);
    }

    private function staff(array $permissions = []): User
    {
        $user = User::factory()->create(['type' => User::TYPE_STAFF]);
        $user->givePermissionTo($permissions);

        return $user;
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Login to your account.');
    }

    public function test_admin_can_log_in_and_customers_cannot(): void
    {
        $admin = User::factory()->create(['type' => User::TYPE_ADMIN, 'password' => 'secret-pass-1']);
        $this->post('/login', ['email' => $admin->email, 'password' => 'secret-pass-1'])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($admin);

        $this->post('/logout');
        $customer = User::factory()->create(['type' => User::TYPE_CUSTOMER, 'password' => 'secret-pass-1']);
        $this->post('/login', ['email' => $customer->email, 'password' => 'secret-pass-1'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_banned_staff_is_logged_out(): void
    {
        $staff = $this->staff(['view_dashboard']);
        $staff->update(['banned' => true]);

        $this->actingAs($staff)->get('/admin')->assertRedirect('/login');
    }

    public function test_dashboard_and_placeholder_pages_load_for_super_admin(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin')->assertOk()->assertSee('Total Sales Revenue');

        $this->get('/admin/soon/no-such-page')->assertNotFound(); // every planned page is built
    }

    public function test_every_sidebar_link_opens(): void
    {
        $this->actingAs($this->admin());
        $html = $this->get('/admin')->assertOk()->getContent();

        preg_match_all('#<aside.*?</aside>#s', $html, $aside);
        preg_match_all('#href="(https?://[^"/]+/admin[^"]*)"#', $aside[0][0], $links);

        $this->assertNotEmpty($links[1]);
        foreach (array_unique($links[1]) as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_staff_permissions_guard_routes_and_hide_menu_items(): void
    {
        $staff = $this->staff(['view_dashboard']);

        $this->actingAs($staff)->get('/admin')->assertOk()->assertDontSee('Uploaded Files');
        $this->get('/admin/uploaded-files')->assertForbidden();

        $staff->givePermissionTo('view_uploads');
        $this->get('/admin')->assertSee('Uploaded Files');
        $this->get('/admin/uploaded-files')->assertOk();
    }

    public function test_images_are_converted_to_webp(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $response = $this->postJson('/admin/uploaded-files', [
            'file' => UploadedFile::fake()->image('banner.png', 2400, 1200),
        ])->assertOk();

        $upload = Upload::query()->findOrFail($response->json('data.id'));
        $this->assertSame('webp', $upload->extension);
        $this->assertSame(2000, $upload->width); // scaled down to the 2000px limit
        Storage::disk('public')->assertExists($upload->file_name);

        $this->deleteJson("/admin/uploaded-files/{$upload->id}")->assertOk();
        Storage::disk('public')->assertMissing($upload->file_name);
    }

    public function test_settings_endpoint_saves_and_get_setting_reads_it(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/admin/business-settings/update', [
                'types' => ['system_name', 'header_menu'],
                'system_name' => 'Jihan Mart BD',
                'header_menu' => [['label' => 'Offers', 'link' => '/offers']],
            ])->assertOk()->assertJson(['success' => true]);

        $this->assertSame('Jihan Mart BD', get_setting('system_name'));
        $this->assertSame('Offers', json_decode(get_setting('header_menu'), true)[0]['label']);
        $this->assertSame(1, BusinessSetting::query()->where('type', 'system_name')->count());
    }

    public function test_settings_endpoint_needs_a_settings_permission(): void
    {
        $this->actingAs($this->staff(['view_dashboard']))
            ->postJson('/admin/business-settings/update', ['types' => ['system_name'], 'system_name' => 'x'])
            ->assertForbidden();
    }

    public function test_clean_bd_phone(): void
    {
        $this->assertSame('01712345678', cleanBdPhone('+880 1712-345678'));
        $this->assertSame('01712345678', cleanBdPhone('8801712345678'));
        $this->assertSame('01712345678', cleanBdPhone('০১৭১২৩৪৫৬৭৮'));
        $this->assertNull(cleanBdPhone('0171234'));
        $this->assertNull(cleanBdPhone('01212345678')); // 012 is not a mobile prefix
    }

    public function test_money_and_date_format(): void
    {
        $this->assertSame('৳1,234.50', format_price(1234.5));
        $this->assertSame('24-10-2026 03:45 PM', format_date('2026-10-24 09:45:00 UTC'));
    }
}
