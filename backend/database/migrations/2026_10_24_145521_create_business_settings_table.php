<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // one row per setting; `lang` is set only for translatable values
        Schema::create('business_settings', function (Blueprint $table) {
            $table->id();
            $table->string('type', 120);
            $table->longText('value')->nullable();
            $table->string('lang', 10)->nullable();
            $table->timestamps();

            $table->unique(['type', 'lang']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_settings');
    }
};
