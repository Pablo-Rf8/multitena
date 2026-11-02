<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['pepsi-demo' => 'pepsi-demo.test', 'coca-cola-demo' => 'coca-cola-demo.test'] as $slug => $domain) {
            $company = DB::table('companies')->where('slug', $slug)->first();
            if (! $company) continue;

            $settings = DB::table('company_settings')->where('company_id', $company->id)->first();
            if ($settings && $settings->email_domains && $settings->email_domains !== '[]') continue;
            $claimed = DB::table('company_settings')->where('company_id', '!=', $company->id)
                ->whereRaw("EXISTS (SELECT 1 FROM json_each(company_settings.email_domains) WHERE value = ?)", [$domain])->exists();
            if ($claimed) continue;

            if ($settings) {
                DB::table('company_settings')->where('company_id', $company->id)->update(['email_domains' => json_encode([$domain]), 'updated_at' => now()]);
            } else {
                DB::table('company_settings')->insert(['company_id' => $company->id, 'email_domains' => json_encode([$domain]), 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        // Los dominios configurados por una empresa se conservan al revertir la migración.
    }
};
