<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Gate;
use App\Models\Credential;
use App\Models\CredentialTemplate;
use App\Policies\CredentialPolicy;
use App\Policies\CredentialTemplatePolicy;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::policy(Credential::class, CredentialPolicy::class);
        Gate::policy(CredentialTemplate::class, CredentialTemplatePolicy::class);
        View::addLocation(base_path('../frontend/views'));
        if (app()->environment('production') && str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceRootUrl(config('app.url'));
            URL::forceScheme('https');
        }
    }
}
