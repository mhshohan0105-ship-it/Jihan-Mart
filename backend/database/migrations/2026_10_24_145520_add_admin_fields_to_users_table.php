<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // admin, staff, customer, reseller
            $table->string('type', 20)->default('customer')->after('id')->index();
            $table->string('phone', 20)->nullable()->after('email')->index();
            $table->unsignedBigInteger('avatar_upload_id')->nullable()->after('phone');
            $table->boolean('banned')->default(false)->after('avatar_upload_id');
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
        });

        // guests and phone only customers have no email
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['type', 'phone', 'avatar_upload_id', 'banned', 'last_login_at']);
        });
    }
};
