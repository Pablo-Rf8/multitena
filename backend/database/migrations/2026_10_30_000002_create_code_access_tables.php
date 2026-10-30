<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('mobile_sessions', fn (Blueprint $table) => $table->string('auth_method', 12)->default('password'));

        Schema::create('code_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('secret');
            $table->unsignedBigInteger('last_used_step')->nullable();
            $table->timestamps();
        });

        Schema::create('code_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->text('handoff_token')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamp('issued_at');
            $table->timestamp('expires_at');
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'status', 'expires_at']);
            $table->index(['user_id', 'issued_at']);
        });

        Schema::create('code_auth_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role', 30)->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('result', 10);
            $table->string('reason', 60)->nullable();
            $table->char('session_hash', 64)->nullable();
            $table->timestamp('created_at');
            $table->index(['company_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['result', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('code_auth_attempts');
        Schema::dropIfExists('code_links');
        Schema::dropIfExists('code_credentials');
        Schema::table('mobile_sessions', fn (Blueprint $table) => $table->dropColumn('auth_method'));
    }
};
