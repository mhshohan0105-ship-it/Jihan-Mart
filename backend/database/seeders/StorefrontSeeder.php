<?php

namespace Database\Seeders;

use App\Models\BusinessSetting;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Database\Seeder;

/** Bangla names + emoji for the storefront categories, and the three Mystery Box products. Safe to run again. */
class StorefrontSeeder extends Seeder
{
    private const STYLE = [
        'Cosmetic And Beauty' => ['কসমেটিক ও বিউটি', '💄'], 'Jewellery' => ['জুয়েলারি', '💍'], 'Makeup' => ['মেকআপ', '💋'],
        'Wear & Under Garments' => ['পোশাক ও আন্ডার গার্মেন্টস', '👗'], 'Perfume, Fragrance' => ['পারফিউম ও সুগন্ধি', '🌸'],
        'Perfume Fragrance' => ['পারফিউম ও সুগন্ধি', '🌸'], 'Chocolate' => ['চকলেট', '🍫'], "Women's Bag" => ['মেয়েদের ব্যাগ', '👜'],
        'Bath' => ['বাথ', '🛁'], 'Hair Accessories' => ['হেয়ার অ্যাক্সেসরিজ', '🎀'], 'Pet Items' => ['পেট আইটেম', '🐾'],
        'Mobile & Gadget' => ['মোবাইল ও গ্যাজেট', '📱'], 'Kitchen And Baking' => ['কিচেন ও বেকিং', '🍳'], 'Baby Items' => ['বেবি আইটেম', '🍼'],
        'Light & Decoration' => ['লাইট ও ডেকোরেশন', '💡'], 'Stationary Item' => ['স্টেশনারি', '✏️'], 'Sunglass' => ['সানগ্লাস', '🕶️'],
        'Others Accessories' => ['অন্যান্য অ্যাক্সেসরিজ', '🧩'], 'Birthday & Party Item' => ['বার্থডে ও পার্টি', '🎉'],
        "Women's Watch" => ['মেয়েদের ঘড়ি', '⌚'], 'Home Decor' => ['হোম ডেকর', '🏠'], 'Mystery Box' => ['মিস্ট্রি বক্স', '🎁'],
        'Skin Care' => ['স্কিন কেয়ার', null], 'Lipstick' => ['লিপস্টিক', null], 'Makeup Brush & Tools' => ['মেকআপ ব্রাশ ও টুলস', null],
        'Eye Accessories' => ['আই অ্যাক্সেসরিজ', null], 'Lip Care' => ['লিপ কেয়ার', null], 'Nails' => ['নেইলস', null],
        'Necklace' => ['নেকলেস', null], 'Crown & Headpiece' => ['ক্রাউন ও হেডপিস', null], 'Necklace Set' => ['নেকলেস সেট', null],
        'Hijab Brooch & Hijab Pin' => ['হিজাব ব্রোচ ও পিন', null], 'Bracelets & Payel' => ['ব্রেসলেট ও পায়েল', null],
        'Earrings' => ['কানের দুল', null], 'Finger Rings' => ['ফিঙ্গার রিং', null],
    ];

    public function run(): void
    {
        foreach (Category::query()->get() as $category) {
            [$bn, $emoji] = self::STYLE[$category->name] ?? [null, null];
            $category->update(['name_bn' => $category->name_bn ?: $bn, 'icon' => $category->icon ?: $emoji]);
        }

        // Mystery boxes are normal products so checkout, stock and orders treat them like anything else
        $category = Category::query()->firstOrCreate(['name' => 'Mystery Box', 'parent_id' => null], ['name_bn' => 'মিস্ট্রি বক্স', 'icon' => '🎁']);
        $collection = Collection::query()->firstOrCreate(['name' => 'Mystery Box'], ['status' => true]);
        foreach (['Mystery Box Mini' => 499, 'Mystery Box Plus' => 999, 'Mystery Box Royal' => 1999] as $name => $price) {
            if (Product::query()->where('name', $name)->exists()) {
                continue;
            }
            $product = app(ProductService::class)->save(new Product, [
                'name' => $name, 'category_ids' => [$category->id], 'unit_price' => $price, 'discount' => 0, 'discount_type' => 'flat',
                'qty' => 100, 'min_qty' => 1, 'payment_policy' => 'full_cod', 'partial_payment_type' => 'flat', 'published' => true,
                'description' => '<p>4 to 6 handpicked surprise items worth more than the box price.</p>',
            ]);
            $collection->products()->syncWithoutDetaching([$product->id]);
        }

        BusinessSetting::query()->firstOrCreate(['type' => 'review_auto_approve', 'lang' => null], ['value' => '0']);
    }
}
