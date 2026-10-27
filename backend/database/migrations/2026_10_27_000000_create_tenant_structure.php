<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_central')->default(false);
            $table->timestamps();
            $table->unique(['company_id', 'name']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('company_admin');
            $table->foreignId('company_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
            $table->dropConstrainedForeignId('company_id');
            $table->dropColumn('role');
        });
        Schema::dropIfExists('branches');
        Schema::dropIfExists('companies');
    }
};
