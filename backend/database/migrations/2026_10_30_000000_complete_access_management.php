<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('categories', fn (Blueprint $table) => $table->boolean('is_active')->default(true));
        Schema::table('mobile_sessions', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->change();
            $table->foreignId('device_id')->nullable()->change();
            $table->string('channel', 10)->default('mobile');
            $table->string('web_session_hash', 64)->nullable()->unique();
            $table->timestamp('last_seen_at')->nullable();
        });
        Schema::table('access_notifications', function (Blueprint $table) {
            $table->foreignId('recipient_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title')->nullable();
            $table->string('body')->nullable();
        });
        Schema::table('devices', fn (Blueprint $table) => $table->text('push_token')->nullable());
    }

    public function down(): void
    {
        Schema::table('devices', fn (Blueprint $table) => $table->dropColumn('push_token'));
        Schema::table('access_notifications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recipient_id');
            $table->dropColumn(['title', 'body']);
        });
        Schema::table('mobile_sessions', function (Blueprint $table) {
            $table->dropColumn(['channel', 'web_session_hash', 'last_seen_at']);
            $table->foreignId('device_id')->nullable(false)->change();
            $table->foreignId('company_id')->nullable(false)->change();
        });
        Schema::table('categories', fn (Blueprint $table) => $table->dropColumn('is_active'));
    }
};
