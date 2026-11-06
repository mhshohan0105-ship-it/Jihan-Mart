<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->default('custom'); // home, default (policy pages), custom
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content')->nullable();
            $table->string('position', 30)->nullable(); // footer column for the link
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('keywords')->nullable();
            $table->unsignedBigInteger('meta_image_upload_id')->nullable();
            $table->timestamps();
        });

        Schema::create('landing_pages', function (Blueprint $table) {
            $table->id();
            $table->string('domain')->unique();
            $table->unsignedBigInteger('logo_upload_id')->nullable();
            $table->json('sliders')->nullable();        // [{image, link}]
            $table->string('video_link')->nullable();
            $table->json('features')->nullable();       // [{image, text}]
            $table->json('banners')->nullable();        // {banner1: [{image, link}], banner2, banner3, two, three}
            $table->json('product_ids')->nullable();
            $table->json('contents')->nullable();       // [{title, body}] x3
            $table->longText('about')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_pages');
        Schema::dropIfExists('pages');
    }
};
