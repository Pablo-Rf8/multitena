<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Multitena')</title>
    <link rel="stylesheet" href="{{ route('assets.css') }}">
    <script src="{{ route('assets.js') }}" defer></script>
</head>
<body>
    <header class="site-header">
        <div class="header-inner">
            <a class="brand" href="{{ url('/') }}">Multitena</a>
            @auth
                <nav class="main-nav" aria-label="Navegación principal">
                    <a href="{{ route('dashboard') }}" @class(['current' => request()->routeIs('dashboard', 'companies.*', 'branches.*', 'branch-users.*', 'company-admins.*')])>Panel</a>
                    @if (auth()->user()->role !== 'super_admin')
                        <a href="{{ route('contacts.index') }}" @class(['current' => request()->routeIs('contacts.*')])>Contactos</a>
                        <a href="{{ route('opportunities.index') }}" @class(['current' => request()->routeIs('opportunities.*')])>Oportunidades</a>
                    @endif
                    @if (auth()->user()->role === 'company_admin')
                        <a href="{{ route('activity.index') }}" @class(['current' => request()->routeIs('activity.*')])>Actividad</a>
                    @endif
                </nav>
                <div class="account">
                    <span>{{ auth()->user()->name }}</span>
                    <a href="{{ route('password.edit') }}">Cambiar contraseña</a>
                    <form method="post" action="{{ route('logout') }}">
                        @csrf
                        <button class="text-button" type="submit">Cerrar sesión</button>
                    </form>
                </div>
            @endauth
        </div>
    </header>
    <main class="page">
        @if (session('status'))
            <div class="notice" role="status">{{ session('status') }}</div>
        @endif
        @error('administrator')
            <div class="notice notice-error" role="alert">{{ $message }}</div>
        @enderror
        @error('branch')
            <div class="notice notice-error" role="alert">{{ $message }}</div>
        @enderror
        @yield('content')
    </main>
</body>
</html>
