@extends('layout')

@section('title', 'Ingresar · Multitena')

@section('content')
<section class="panel narrow">
    <p class="eyebrow">Acceso</p>
    <h1>Ingresá a tu cuenta</h1>
    <p class="intro">Administrá tus empresas o consultá la sucursal que tenés asignada.</p>
    <form method="post" action="{{ route('login') }}" class="form-stack">
        @csrf
        <label for="email">Correo electrónico</label>
        <input id="email" name="email" type="email" autocomplete="username" value="{{ old('email') }}" required autofocus>
        @error('email') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        <label for="password">Contraseña</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>
        @error('password') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        <button class="button" type="submit">Ingresar</button>
    </form>
    <p class="form-foot"><a href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a></p>
</section>
@endsection
