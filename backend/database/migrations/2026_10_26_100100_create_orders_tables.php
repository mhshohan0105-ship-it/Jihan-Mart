<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('code')->unique(); // running order number shown to people
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // customer
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); // staff for POS orders
            $table->string('name', 120);
            $table->string('phone', 20)->index();
            $table->string('email')->nullable();
            $table->text('address');
            $table->foreignId('state_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source', 20)->default('website'); // website, direct, landing, pos, abandoned
            $table->string('landing_domain')->nullable();
            $table->string('payment_method', 30)->default('cod');
            $table->string('payment_status', 20)->default('unpaid')->index(); // unpaid, partial_paid, paid
            $table->string('delivery_status', 20)->default('pending')->index();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('coupon_discount', 12, 2)->default(0);
            $table->string('coupon_code', 40)->nullable();
            $table->decimal('advance_amount', 12, 2)->default(0);
            $table->string('advance_method', 20)->nullable();
            $table->string('sender_no', 20)->nullable();
            $table->string('transaction_id', 60)->nullable();
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->decimal('due', 12, 2)->default(0);
            $table->string('tracking_code', 60)->nullable();
            $table->string('courier', 20)->nullable();
            $table->string('consignment_id', 60)->nullable()->index();
            $table->text('admin_note')->nullable();
            $table->text('customer_note')->nullable();
            $table->boolean('viewed')->default(false);
            $table->boolean('stock_restored')->default(false);
            $table->timestamps();
        });

        Schema::create('order_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('variant')->default('');
            $table->string('sku', 60)->nullable();
            $table->decimal('price', 12, 2);
            $table->unsignedInteger('quantity');
            $table->decimal('total', 12, 2);
        });

        Schema::create('order_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action_type', 30);
            $table->string('title');
            $table->string('old_value')->nullable();
            $table->string('new_value')->nullable();
            $table->text('note')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('order_call_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone', 20);
            $table->string('call_status', 20)->default('called');
            $table->text('note')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('abandoned_carts', function (Blueprint $table) {
            $table->id();
            $table->string('session_key', 64)->nullable()->index();
            $table->string('name', 120)->nullable();
            $table->string('phone', 20)->index();
            $table->text('address')->nullable();
            $table->foreignId('state_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->json('items'); // [{product_id, variant, quantity, price, name}]
            $table->string('source', 120)->default('website');
            $table->string('status', 12)->default('pending'); // pending, converted
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abandoned_carts');
        Schema::dropIfExists('order_call_logs');
        Schema::dropIfExists('order_activities');
        Schema::dropIfExists('order_details');
        Schema::dropIfExists('orders');
    }
};
