@extends('layout')

@section('title', $loginCompany ? 'Ingresar · '.$loginCompany->name : ($platformLogin ? 'Acceso global · Multitena' : 'Elegir empresa · Multitena'))

@section('content')
<section class="panel narrow login-panel">
    @if($loginCompany || $platformLogin)
        <div class="login-identity">
            @if($loginCompany && $loginLogo)<img class="login-logo" src="{{ $loginLogo }}" alt="" width="64" height="64">@endif
            <div>
                <p class="eyebrow">{{ $platformLogin ? 'Administración de plataforma' : 'Acceso de empresa' }}</p>
                <h1>{{ $platformLogin ? 'Ingresar a Multitena' : $loginCompany->name }}</h1>
            </div>
        </div>
        <p class="intro">{{ $platformLogin ? 'Acceso exclusivo para superadministradores.' : 'Ingresá con tu correo y contraseña para acceder a tu empresa.' }}</p>
        <form method="post" action="{{ route('login') }}" class="form-stack">
            @csrf
            <label for="email">Correo electrónico</label>
            <input id="email" name="email" type="email" autocomplete="username" value="{{ old('email') }}" required autofocus>
            @error('email')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            <label for="password">Contraseña</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
            @error('password')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            <button class="button" type="submit">Ingresar</button>
        </form>
        <div class="login-links">
            @if($loginCompany)<a href="{{ route('login.code') }}">Ingresar con código temporal</a>@endif
            <a href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
            <a href="{{ route('login.change') }}">Elegir otro acceso</a>
        </div>
    @else
        <p class="eyebrow">Acceso seguro</p>
        <h1>Elegí tu empresa</h1>
        <p class="intro">Introducí el código que te proporcionó el administrador de tu empresa.</p>
        <form method="post" action="{{ route('login.company') }}" class="form-stack">
            @csrf
            <label for="company_code">Código de empresa</label>
            <input id="company_code" name="company_code" autocomplete="organization" maxlength="32" value="{{ old('company_code') }}" required autofocus>
            @error('company_code')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            <button class="button" type="submit">Continuar</button>
        </form>
        <div class="login-links"><a href="{{ route('login.platform') }}">Acceso de superadministrador</a></div>
    @endif
</section>
@endsection
