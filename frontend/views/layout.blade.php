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
        @yield('content')
    </main>
</body>
</html>
