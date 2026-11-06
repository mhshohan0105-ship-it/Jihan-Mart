<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flash_deals', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('background_color', 9)->default('#f47c20');
            $table->string('text_color', 10)->default('white'); // white, dark
            $table->unsignedBigInteger('banner_upload_id')->nullable();
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->boolean('status')->default(true);
            $table->boolean('featured')->default(false); // shown on the home page
            $table->timestamps();
        });

        Schema::create('flash_deal_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flash_deal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('discount', 12, 2)->default(0);
            $table->string('discount_type', 10)->default('percent');
            $table->unique(['flash_deal_id', 'product_id']);
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('type', 10)->default('cart'); // cart (total order), product
            $table->string('code', 40)->unique();
            $table->decimal('discount', 12, 2);
            $table->string('discount_type', 10)->default('flat'); // flat, percent
            $table->decimal('min_buy', 12, 2)->default(0);
            $table->decimal('max_discount', 12, 2)->nullable();
            $table->json('product_ids')->nullable();
            $table->json('user_ids')->nullable(); // limit to these customers
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('usage_limit_per_user')->nullable();
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone', 20)->nullable()->index();
            $table->decimal('amount', 12, 2);
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->nullable()->unique();
            $table->string('phone', 20)->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['subscribers', 'coupon_usages', 'coupons', 'flash_deal_products', 'flash_deals'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
