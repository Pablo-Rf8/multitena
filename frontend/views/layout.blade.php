<!doctype html>
<html lang="{{ app()->getLocale() }}">
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
    <link rel="stylesheet" href="{{ route('assets.theme') }}?v={{ filemtime(base_path('../frontend/css/theme.css')) }}">
    <script src="{{ route('assets.js') }}?v={{ filemtime(base_path('../frontend/js/app.js')) }}" defer></script>
</head>
<body class="{{ $isPlatform ? 'theme-platform' : ($brandCompany ? 'theme-tenant' : 'theme-public') }}" style="{{ $themeStyle }}">
<svg class="icon-sprite" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <symbol id="icon-home" viewBox="0 0 24 24"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1zM9 21v-7h6v7"/></symbol>
    <symbol id="icon-bell" viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></symbol>
    <symbol id="icon-users" viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3 20v-2a6 6 0 0 1 12 0v2zM17 5a3 3 0 0 1 0 6M18 14a5 5 0 0 1 3 5v1"/></symbol>
    <symbol id="icon-user" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/></symbol>
    <symbol id="icon-briefcase" viewBox="0 0 24 24"><rect x="3" y="7" width="18" height="14" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 13c5 3 13 3 18 0"/></symbol>
    <symbol id="icon-shield" viewBox="0 0 24 24"><path d="m12 2 9 4v6c0 5-3.5 8-9 10-5.5-2-9-5-9-10V6zM9 12l2 2 4-4"/></symbol>
    <symbol id="icon-settings" viewBox="0 0 24 24"><path d="M4 7h16M4 17h16M8 4v6M16 14v6"/></symbol>
    <symbol id="icon-palette" viewBox="0 0 24 24"><path d="M12 3a9 9 0 1 0 0 18h2a2 2 0 0 0 2-2c0-1-.5-1.5-.5-2.5a2 2 0 0 1 2-2H19a2 2 0 0 0 2-2A9 9 0 0 0 12 3z"/><circle cx="7" cy="12" r="1"/><circle cx="10" cy="7" r="1"/><circle cx="16" cy="8" r="1"/></symbol>
    <symbol id="icon-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></symbol>
    <symbol id="icon-key" viewBox="0 0 24 24"><circle cx="7" cy="15" r="4"/><path d="m10 12 9-9 2 2-2 2 2 2-3 3-2-2-3 3"/></symbol>
    <symbol id="icon-logout" viewBox="0 0 24 24"><path d="M10 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h5M14 7l5 5-5 5M8 12h11"/></symbol>
    <symbol id="icon-globe" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c-2.5 2.5-4 5.5-4 9s1.5 6.5 4 9M12 3c2.5 2.5 4 5.5 4 9s-1.5 6.5-4 9"/></symbol>
    <symbol id="icon-chevron-down" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></symbol>
    <symbol id="icon-plus" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></symbol>
    <symbol id="icon-close" viewBox="0 0 24 24"><path d="M5 5l14 14M19 5 5 19"/></symbol>
</svg>
<a class="skip-link" href="#main-content">{{ __('Ir al contenido') }}</a>
<div class="{{ $viewer ? 'app-shell' : 'public-shell' }}">
    @if($viewer)
        <aside class="sidebar" id="sidebar" aria-label="{{ __('Navegación de') }} {{ $brandName }}">
            <div class="sidebar-top">
                <a class="brand" href="{{ route('dashboard') }}">
                    @if(!$isPlatform && $brandLogo)
                        <img class="brand-logo" src="{{ $brandLogo }}" alt="" width="42" height="42">
                    @else
                        <span class="brand-mark" aria-hidden="true">{{ $isPlatform ? 'M' : mb_strtoupper(mb_substr($brandName, 0, 1)) }}</span>
                    @endif
                    <span class="brand-copy"><strong>{{ $brandName }}</strong><small>{{ $isPlatform ? __('Administración global') : __('Espacio de trabajo') }}</small></span>
                </a>
                <button class="sidebar-close" type="button" data-sidebar-close aria-label="{{ __('Cerrar menú') }}">×</button>
            </div>
            @include('partials.navigation', ['viewer' => $viewer])
            <div class="sidebar-bottom">
                <button class="account-trigger" type="button" data-account-open aria-haspopup="dialog" aria-controls="account-dialog">
                    <svg class="icon" aria-hidden="true"><use href="#icon-user"/></svg> {{ __('Mi cuenta') }}
                    <span class="account-trigger-arrow" aria-hidden="true">⌃</span>
                </button>
            </div>
        </aside>
        <div class="sidebar-scrim" data-sidebar-close></div>
    @endif
    <div class="workspace">
        <header class="site-header">
            <div class="header-inner">
                @if($viewer)<button class="menu-toggle" type="button" data-sidebar-open aria-controls="sidebar" aria-expanded="false" aria-label="{{ __('Abrir menú') }}">☰</button>@endif
                <div class="header-context"><span class="eyebrow">{{ $isPlatform ? __('Plataforma') : ($brandCompany?->name ?? __('Acceso')) }}</span><strong>@yield('page-label', $viewer ? (request()->routeIs('dashboard') ? __('Panel de control') : trim(explode('·', $__env->yieldContent('title', __('Espacio de trabajo')))[0])) : __('Acceso seguro'))</strong></div>
                @unless($viewer)
                    @include('partials.language-switcher')
                    <span class="public-brand">@if($brandLogo)<img src="{{ $brandLogo }}" alt="" width="32" height="32">@endif {{ $brandName }}</span>
                @endunless
            </div>
        </header>
        <main class="page" id="main-content">
            <div class="loading-indicator" role="status" aria-live="polite">{{ __('Guardando…') }}</div>
            @if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
            @if($errors->has('administrator') || $errors->has('branch'))<div class="notice notice-error" role="alert">{{ $errors->first('administrator') ?: $errors->first('branch') }}</div>@endif
            @yield('content')
        </main>
    </div>
</div>
@if($viewer)
<dialog class="account-dialog" id="account-dialog" aria-labelledby="account-dialog-title" @if($errors->accountPassword->has('current_password') || $errors->accountPassword->has('password')) data-account-error="true" @endif @if(session('reopen_account')) data-reopen-account="true" @endif>
    <div class="account-dialog-inner">
        <div class="account-dialog-head">
            <span class="account-dialog-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($viewer->name, 0, 1)) }}</span>
            <div class="account-dialog-person"><h2 id="account-dialog-title">{{ $viewer->name }}</h2><p class="muted">{{ $viewer->email }}</p></div>
            <button type="button" class="dialog-close" data-account-close aria-label="{{ __('Cerrar ventana') }}"><svg class="icon" aria-hidden="true"><use href="#icon-close"/></svg></button>
        </div>
        <div data-account-menu>
            <div class="account-language">@include('partials.language-switcher', ['inAccount' => true])</div>
            @if($viewer->role === 'company_admin')
                <nav class="account-menu" aria-label="{{ __('Configuración de la empresa') }}">
                    <p class="account-menu-label">{{ __('Configuración') }}</p>
                    <a href="{{ route('branding.edit') }}"><svg class="icon" aria-hidden="true"><use href="#icon-palette"/></svg><span>{{ __('Preferencias') }}<small>{{ __('Colores y logotipo') }}</small></span></a>
                    <a href="{{ route('activity.index') }}"><svg class="icon" aria-hidden="true"><use href="#icon-clock"/></svg><span>{{ __('Actividad') }}</span></a>
                </nav>
            @endif
            <button class="account-menu-action" type="button" data-account-password-open><svg class="icon" aria-hidden="true"><use href="#icon-key"/></svg>{{ __('Cambiar contraseña') }}</button>
            <div class="account-dialog-footer">
                <form method="post" action="{{ route('logout') }}">@csrf<button class="button button-ghost" type="submit" data-confirm="{{ __('¿Seguro que quieres cerrar sesión?') }}"><svg class="icon" aria-hidden="true"><use href="#icon-logout"/></svg>{{ __('Cerrar sesión') }}</button></form>
            </div>
        </div>
        <div class="account-password-panel" data-account-password-panel hidden>
            <button class="account-back" type="button" data-account-password-back>← {{ __('Volver') }}</button>
            <h3>{{ __('Cambiar contraseña') }}</h3>
            <form method="post" action="{{ route('password.update') }}" class="form-stack account-password-form">
                @csrf @method('PUT')
                <label for="modal-current-password">{{ __('Contraseña actual') }}</label>
                <input id="modal-current-password" name="current_password" type="password" autocomplete="current-password" required>
                @error('current_password', 'accountPassword')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                <label for="modal-new-password">{{ __('Nueva contraseña') }}</label>
                <input id="modal-new-password" name="password" type="password" minlength="12" autocomplete="new-password" required>
                <p class="hint">{{ __('Mínimo 12 caracteres.') }}</p>
                @error('password', 'accountPassword')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                <label for="modal-confirm-password">{{ __('Confirmar contraseña') }}</label>
                <input id="modal-confirm-password" name="password_confirmation" type="password" minlength="12" autocomplete="new-password" required>
                <button class="button button-primary" type="submit"><svg class="icon" aria-hidden="true"><use href="#icon-key"/></svg>{{ __('Guardar contraseña') }}</button>
            </form>
        </div>
    </div>
</dialog>
<dialog class="confirm-dialog" id="confirm-dialog" aria-labelledby="confirm-dialog-title" aria-describedby="confirm-dialog-message">
    <div class="confirm-dialog-inner">
        <h2 id="confirm-dialog-title" data-confirm-title>{{ __('Confirmar acción') }}</h2>
        <p id="confirm-dialog-message" data-confirm-message></p>
        <div class="confirm-dialog-actions">
            <button class="button button-ghost" type="button" data-confirm-cancel>{{ __('Cancelar') }}</button>
            <button class="button button-primary" type="button" data-confirm-accept>{{ __('Confirmar') }}</button>
        </div>
    </div>
</dialog>
@endif
</body>
</html>
