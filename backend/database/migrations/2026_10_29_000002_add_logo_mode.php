<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('companies', fn (Blueprint $table) => $table->string('logo_mode', 10)->default('custom'));
        DB::table('companies')->whereIn('slug', ['pepsi-demo', 'coca-cola-demo'])->update(['logo_mode' => 'demo']);
    }

    public function down(): void
    {
        Schema::table('companies', fn (Blueprint $table) => $table->dropColumn('logo_mode'));
    }
};
