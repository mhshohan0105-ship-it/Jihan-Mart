<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // courier delivery history per phone, so the order list does not call couriers on every page load
        Schema::create('fraud_check_cache', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->unique();
            $table->json('data');
            $table->timestamp('checked_at');
        });

        // a district can belong to a different Pathao city than its zone, so the mapping lives on the area
        Schema::table('cities', function (Blueprint $table) {
            $table->unsignedInteger('pathao_city_id')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fraud_check_cache');
        Schema::table('cities', fn (Blueprint $table) => $table->dropColumn('pathao_city_id'));
    }
};
