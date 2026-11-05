<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credential_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('credential_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('shared_by_id')->constrained('users')->restrictOnDelete();
            $table->boolean('can_reveal')->default(false);
            $table->unsignedInteger('max_accesses')->nullable();
            $table->unsignedInteger('used_accesses')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'user_id', 'revoked_at']);
            $table->index(['credential_id', 'user_id']);
        });

        Schema::create('credential_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credential_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['credential_id', 'user_id']);
        });

        Schema::create('credential_accesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('credential_id')->constrained()->cascadeOnDelete();
            $table->foreignId('share_id')->nullable()->constrained('credential_shares')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('opened_at');
            $table->index(['credential_id', 'opened_at']);
        });

        Schema::create('credential_template_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('template_id')->constrained('credential_templates')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('shared_by_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('max_accesses')->nullable();
            $table->unsignedInteger('used_accesses')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'user_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credential_template_shares');
        Schema::dropIfExists('credential_accesses');
        Schema::dropIfExists('credential_favorites');
        Schema::dropIfExists('credential_shares');
    }
};
