<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Product reviews come from real buyers: the review keeps the delivered order it was checked against. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->boolean('verified')->default(false)->after('order_id');
            $table->unique(['order_id', 'product_id']); // one review per product per order
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropUnique(['order_id', 'product_id']);
            $table->dropConstrainedForeignId('order_id');
            $table->dropColumn('verified');
        });
    }
};
