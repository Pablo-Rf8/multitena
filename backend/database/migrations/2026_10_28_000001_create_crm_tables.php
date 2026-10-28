<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->string('name', 120);
            $table->string('organization', 120)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('status', 20)->default('prospect');
            $table->string('source', 80)->nullable();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
            $table->index(['company_id', 'branch_id', 'status']);
        });

        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('contact_id')->constrained()->restrictOnDelete();
            $table->string('title', 160);
            $table->unsignedBigInteger('amount_cents')->default(0);
            $table->string('stage', 20)->default('new');
            $table->date('expected_close_on')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
            $table->index(['company_id', 'branch_id', 'stage']);
        });

        Schema::create('contact_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->restrictOnDelete();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('event', 80);
            $table->string('subject_type', 40);
            $table->unsignedBigInteger('subject_id');
            $table->json('properties')->nullable();
            $table->timestamp('created_at');
            $table->index(['company_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('contact_notes');
        Schema::dropIfExists('opportunities');
        Schema::dropIfExists('contacts');
    }
};
