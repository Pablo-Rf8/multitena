<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('theme_primary', 7)->default('#245b51');
            $table->string('theme_secondary', 7)->default('#163e39');
            $table->string('theme_accent', 7)->default('#b5d98c');
            $table->string('logo_path')->nullable();
        });
        DB::table('companies')->where('slug', 'pepsi-demo')->update(['theme_primary' => '#0754a6', 'theme_secondary' => '#102d5a', 'theme_accent' => '#d22739']);
        DB::table('companies')->where('slug', 'coca-cola-demo')->update(['theme_primary' => '#bd1930', 'theme_secondary' => '#711323', 'theme_accent' => '#f5d6d9']);
    }

    public function down(): void
    {
        Schema::table('companies', fn (Blueprint $table) => $table->dropColumn(['theme_primary', 'theme_secondary', 'theme_accent', 'logo_path']));
    }
};
