<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanySetting;
use Illuminate\Validation\ValidationException;

class CompanyDomains
{
    public function parse(?string $input): array
    {
        $domains = array_values(array_unique(array_filter(array_map(
            fn ($value) => strtolower(ltrim(trim($value), '@')),
            explode(',', $input ?? '')
        ))));
        foreach ($domains as $domain) {
            if (! filter_var('usuario@'.$domain, FILTER_VALIDATE_EMAIL) || str_contains($domain, '@')) {
                throw ValidationException::withMessages(['email_domains' => 'Ingresá dominios válidos, separados por coma.']);
            }
        }
        return $domains;
    }

    public function assertAvailable(array $domains, ?int $companyId = null): void
    {
        foreach ($domains as $domain) {
            if (CompanySetting::where('company_id', '!=', $companyId ?? 0)->whereJsonContains('email_domains', $domain)->exists()) {
                throw ValidationException::withMessages(['email_domains' => 'El dominio '.$domain.' ya pertenece a otra empresa.']);
            }
        }
    }

    public function findActive(string $identifier): ?Company
    {
        $identifier = strtolower(trim($identifier));
        if (! str_contains($identifier, '@') && ! str_contains($identifier, '.')) {
            return Company::where('code', strtoupper($identifier))->where('is_active', true)->first();
        }
        $domain = strtolower(ltrim(str_contains($identifier, '@') ? substr(strrchr($identifier, '@'), 1) : $identifier, '@'));
        if (! filter_var('usuario@'.$domain, FILTER_VALIDATE_EMAIL)) return null;
        $settings = CompanySetting::whereJsonContains('email_domains', $domain)->limit(2)->get();
        if ($settings->count() !== 1) return null;
        return Company::whereKey($settings->first()->company_id)->where('is_active', true)->first();
    }
}
