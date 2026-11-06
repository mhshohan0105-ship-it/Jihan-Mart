<?php

namespace Database\Seeders;

use App\Models\BusinessSetting;
use Illuminate\Database\Seeder;

/** Default business settings; existing values are never overwritten. */
class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'system_name' => 'Jihan Mart',
            'system_timezone' => 'Asia/Dhaka',
            'site_name' => 'Jihan Mart',
            'base_color' => '#ff3d8b',
            'base_hover_color' => '#8b5cf6',
            'currency_symbol' => '৳',
            'free_shipping_enabled' => '1',
            'free_shipping_min_amount' => '2000',
            'low_stock_threshold' => '5',
            'invoice_footer' => 'Thanks for shopping from Jihan Mart',
            'coupon_system' => '1',
            'blog_system_activation' => '0',
            'support_system_activation' => '0',
            'affiliate_system_activation' => '0',
        ];

        foreach ($defaults as $type => $value) {
            BusinessSetting::query()->firstOrCreate(['type' => $type, 'lang' => null], ['value' => $value]);
        }
    }
}
