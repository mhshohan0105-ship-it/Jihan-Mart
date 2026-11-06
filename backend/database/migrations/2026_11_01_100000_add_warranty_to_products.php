<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Warranty shown on the product page, e.g. "৬ মাস", "১ বছর", "৫ বছর" (empty = no warranty). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('warranty', 60)->nullable()->after('shipping_days');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('warranty');
        });
    }
};
