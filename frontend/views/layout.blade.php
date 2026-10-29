<!doctype html>
<html lang="es">
@php
    $viewer = auth()->user();
    $isPlatform = $viewer?->role === 'super_admin' || (! $viewer && session('login_mode') === 'platform');
    $brandCompany = $viewer?->company ?? (session('login_mode') === 'company' ? \App\Models\Company::where('is_active', true)->find(session('login_company_id')) : null);
    $brandName = $isPlatform || ! $brandCompany ? 'Multitena' : $brandCompany->name;
    $brandLogo = $brandCompany?->logoUrl(public: ! $viewer);
    $themeStyle = $brandCompany && ! $isPlatform
        ? '--brand-primary:'.$brandCompany->theme_primary.';--brand-secondary:'.$brandCompany->theme_secondary.';--brand-accent:'.$brandCompany->theme_accent.';'
        : '';
@endphp
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $brandName)</title>
    <link rel="stylesheet" href="{{ route('assets.css') }}">
    <link rel="stylesheet" href="{{ route('assets.theme') }}">
    <script src="{{ route('assets.js') }}" defer></script>
</head>
<body class="{{ $isPlatform ? 'theme-platform' : ($brandCompany ? 'theme-tenant' : 'theme-public') }}" style="{{ $themeStyle }}">
<a class="skip-link" href="#main-content">Ir al contenido</a>
<div class="{{ $viewer ? 'app-shell' : 'public-shell' }}">
    @if($viewer)
        <aside class="sidebar" id="sidebar" aria-label="Navegación de {{ $brandName }}">
            <div class="sidebar-top">
                <a class="brand" href="{{ route('dashboard') }}">
                    @if(!$isPlatform && $brandLogo)
                        <img class="brand-logo" src="{{ $brandLogo }}" alt="" width="42" height="42">
                    @else
                        <span class="brand-mark" aria-hidden="true">{{ $isPlatform ? 'M' : mb_strtoupper(mb_substr($brandName, 0, 1)) }}</span>
                    @endif
                    <span class="brand-copy"><strong>{{ $brandName }}</strong><small>{{ $isPlatform ? 'Administración global' : 'Espacio de trabajo' }}</small></span>
                </a>
                <button class="sidebar-close" type="button" data-sidebar-close aria-label="Cerrar menú">×</button>
            </div>
            @include('partials.navigation', ['viewer' => $viewer])
            <div class="sidebar-bottom">
                <span class="account-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($viewer->name, 0, 1)) }}</span>
                <span class="account-identity"><strong>{{ $viewer->name }}</strong><small>{{ ['super_admin'=>'Superadministrador','company_admin'=>'Administrador de empresa','branch_admin'=>'Administrador de sucursal','employee'=>'Empleado'][$viewer->role] ?? $viewer->role }}</small></span>
            </div>
        </aside>
        <div class="sidebar-scrim" data-sidebar-close></div>
    @endif
    <div class="workspace">
        <header class="site-header">
            <div class="header-inner">
                @if($viewer)<button class="menu-toggle" type="button" data-sidebar-open aria-controls="sidebar" aria-expanded="false" aria-label="Abrir menú">☰</button>@endif
                <div class="header-context"><span class="eyebrow">{{ $isPlatform ? 'Plataforma' : ($brandCompany?->name ?? 'Acceso') }}</span><strong>@yield('page-label', $viewer ? (request()->routeIs('dashboard') ? 'Panel de control' : trim(explode('·', $__env->yieldContent('title', 'Espacio de trabajo'))[0])) : 'Acceso seguro')</strong></div>
                @if($viewer)
                <div class="account">
                    <a href="{{ route('password.edit') }}">Mi contraseña</a>
                    <form method="post" action="{{ route('logout') }}">@csrf<button class="text-button" type="submit">Salir</button></form>
                </div>
                @else
                    <span class="public-brand">@if($brandLogo)<img src="{{ $brandLogo }}" alt="" width="32" height="32">@endif {{ $brandName }}</span>
                @endif
            </div>
        </header>
        <main class="page" id="main-content">
            <div class="loading-indicator" role="status" aria-live="polite">Guardando…</div>
            @if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
            @if($errors->has('administrator') || $errors->has('branch'))<div class="notice notice-error" role="alert">{{ $errors->first('administrator') ?: $errors->first('branch') }}</div>@endif
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
