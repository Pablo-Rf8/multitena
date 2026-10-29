<?php

use App\Http\Middleware\EnsureActiveAccount;
use App\Http\Middleware\EnsureCompanyAdmin;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\EnsureTenantUser;
use App\Http\Middleware\EnsureMobileSession;
use App\Http\Middleware\EnsureCrmPermission;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'super_admin' => EnsureSuperAdmin::class,
            'company_admin' => EnsureCompanyAdmin::class,
            'active_account' => EnsureActiveAccount::class,
            'tenant_user' => EnsureTenantUser::class,
            'mobile_session' => EnsureMobileSession::class,
            'crm_permission' => EnsureCrmPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Laravel handles validation and authentication responses.
    })->create();
