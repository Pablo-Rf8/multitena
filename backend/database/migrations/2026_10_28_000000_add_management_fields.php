<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
            $table->string('contact_email')->nullable();
            $table->string('phone', 40)->nullable();
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
            $table->string('address')->nullable();
            $table->string('phone', 40)->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['is_active', 'last_login_at']));
        Schema::table('branches', fn (Blueprint $table) => $table->dropColumn(['is_active', 'address', 'phone']));
        Schema::table('companies', fn (Blueprint $table) => $table->dropColumn(['is_active', 'contact_email', 'phone']));
    }
};
