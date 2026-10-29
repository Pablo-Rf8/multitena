<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('code')->nullable()->unique();
        });
        DB::table('companies')->select('id')->orderBy('id')->get()->each(fn ($company) => DB::table('companies')->where('id', $company->id)->update(['code' => strtoupper(Str::random(12))]));
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('email_domains')->nullable();
            $table->boolean('employee_access_enabled')->default(true);
            $table->boolean('require_device_approval')->default(false);
            $table->unsignedInteger('token_lifetime_hours')->default(720);
            $table->timestamps();
        });
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('permissions');
            $table->timestamps();
            $table->unique(['company_id', 'name']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->string('approval_status')->default('approved');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
        });
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('identifier_hash', 64);
            $table->string('name');
            $table->string('status')->default('approved');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'identifier_hash']);
        });
        Schema::create('mobile_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->foreignId('token_id')->nullable()->unique()->constrained('personal_access_tokens')->nullOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('expires_at');
            $table->string('status')->default('active');
            $table->string('end_reason')->nullable();
            $table->timestamps();
        });
        Schema::create('access_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('kind');
            $table->unsignedBigInteger('subject_id');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_notifications');
        Schema::dropIfExists('mobile_sessions');
        Schema::dropIfExists('devices');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['approval_status', 'reviewed_at']);
        });
        Schema::dropIfExists('categories');
        Schema::dropIfExists('company_settings');
        Schema::table('companies', fn (Blueprint $table) => $table->dropColumn('code'));
    }
};
