<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('product_taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tax_id')->constrained()->cascadeOnDelete();
            $table->decimal('tax', 12, 2)->default(0);
            $table->string('tax_type', 10)->default('percent'); // percent, flat
            $table->unique(['product_id', 'tax_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_taxes');
        Schema::dropIfExists('taxes');
    }
};
