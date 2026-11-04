<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credential_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('name', 120);
            $table->timestamps();
            $table->index(['company_id', 'name']);
        });
        Schema::create('credential_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('credential_templates')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('type', 16);
            $table->unsignedInteger('position');
            $table->boolean('required')->default(false);
            $table->timestamps();
            $table->index(['template_id', 'position']);
        });
        Schema::create('credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('template_id')->constrained('credential_templates')->restrictOnDelete();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->string('title', 160);
            $table->string('visibility', 16)->default('private');
            $table->timestamps();
            $table->index(['company_id', 'visibility']);
        });
        Schema::create('credential_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credential_id')->constrained()->cascadeOnDelete();
            $table->foreignId('field_id')->constrained('credential_fields')->restrictOnDelete();
            $table->text('value')->nullable();
            $table->text('secret_ciphertext')->nullable();
            $table->timestamps();
            $table->unique(['credential_id', 'field_id']);
        });
        Schema::create('credential_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credential_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('level', 12);
            $table->boolean('can_reveal')->default(false);
            $table->timestamps();
            $table->unique(['credential_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credential_grants');
        Schema::dropIfExists('credential_values');
        Schema::dropIfExists('credentials');
        Schema::dropIfExists('credential_fields');
        Schema::dropIfExists('credential_templates');
    }
};
