<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 3);
            $table->boolean('status')->default(true);
        });

        // states = delivery zone / district ("ঢাকার ভিতরে", "ঢাকার বাহিরে"...)
        Schema::create('states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('status')->default(true);
            $table->unsignedInteger('pathao_city_id')->nullable();
        });

        // cities = area / thana with its own delivery charge
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('cost', 10, 2)->default(0);
            $table->boolean('status')->default(true);
            $table->unsignedInteger('pathao_zone_id')->nullable();
            $table->unsignedInteger('pathao_area_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
        Schema::dropIfExists('states');
        Schema::dropIfExists('countries');
    }
};
