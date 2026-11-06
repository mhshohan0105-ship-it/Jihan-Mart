<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // added by
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('unit', 30)->nullable();
            $table->decimal('weight', 8, 3)->nullable();
            $table->unsignedInteger('min_qty')->default(1);
            $table->text('tags')->nullable();
            $table->foreignId('suggestion_category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('barcode', 40)->nullable()->index();

            // media library ids; image_url is a fallback for imported products that point at an external image
            $table->unsignedBigInteger('thumbnail_upload_id')->nullable();
            $table->string('photos')->nullable(); // comma separated upload ids
            $table->string('image_url', 500)->nullable();
            $table->string('video_provider', 20)->nullable();
            $table->string('video_link')->nullable();

            // pricing: unit_price is the base price; discount applies within the optional date range
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->decimal('discount', 12, 2)->default(0);
            $table->string('discount_type', 10)->default('flat'); // flat, percent
            $table->dateTime('discount_start_date')->nullable();
            $table->dateTime('discount_end_date')->nullable();
            $table->integer('current_stock')->default(0); // sum of product_stocks.qty
            $table->string('sku', 60)->nullable()->index();
            $table->string('external_link')->nullable();
            $table->string('external_link_btn', 60)->nullable();

            // variants
            $table->boolean('variant_product')->default(false);
            $table->json('colors')->nullable();         // [color ids]
            $table->json('choice_options')->nullable(); // [{attribute_id, values: []}]

            $table->longText('description')->nullable();
            $table->unsignedBigInteger('pdf_upload_id')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->unsignedBigInteger('meta_upload_id')->nullable();

            // checkout rules per product
            $table->string('payment_policy', 20)->default('full_cod'); // full_cod, partial, prepaid
            $table->string('partial_payment_type', 10)->default('flat'); // flat, percent
            $table->decimal('partial_advance_amount', 12, 2)->default(0);

            $table->boolean('published')->default(true);
            $table->boolean('featured')->default(false);
            $table->boolean('todays_deal')->default(false);
            $table->boolean('free_shipping')->default(false);
            $table->boolean('direct_order')->default(true);
            $table->boolean('landing_page')->default(false);
            $table->unsignedSmallInteger('shipping_days')->nullable();

            $table->unsignedInteger('num_of_sale')->default(0);
            $table->decimal('rating', 3, 2)->default(0);
            $table->unsignedInteger('review_count')->default(0);
            $table->unsignedBigInteger('legacy_id')->nullable()->index(); // id in an imported catalog
            $table->timestamps();

            $table->index(['published', 'featured']);
        });

        Schema::create('category_product', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->primary(['category_id', 'product_id']);
        });

        Schema::create('product_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('variant')->default(''); // '' for simple products, e.g. "Red-XL"
            $table->string('sku', 60)->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->integer('qty')->default(0);
            $table->unsignedBigInteger('image_upload_id')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'variant']);
        });

        Schema::create('product_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('min_qty');
            $table->decimal('price', 12, 2);
        });

        Schema::create('collection_product', function (Blueprint $table) {
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->primary(['collection_id', 'product_id']);
        });

        // product or store reviews; storefront shows only approved ones
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete(); // null = store review
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 80);
            $table->string('phone', 20)->nullable();
            $table->unsignedTinyInteger('rating');
            $table->text('comment');
            $table->unsignedBigInteger('photo_upload_id')->nullable();
            $table->unsignedInteger('helpful_count')->default(0);
            $table->string('status', 12)->default('pending')->index(); // pending, approved, rejected
            $table->text('admin_reply')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('collection_product');
        Schema::dropIfExists('product_tiers');
        Schema::dropIfExists('product_stocks');
        Schema::dropIfExists('category_product');
        Schema::dropIfExists('products');
    }
};
