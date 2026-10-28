@extends('layout')

@section('title', 'Recuperar acceso · Multitena')

@section('content')
<section class="panel narrow">
    <a class="back-link" href="{{ route('login') }}">← Volver al ingreso</a>
    <p class="eyebrow">Recuperar acceso</p>
    <h1>Restablecer contraseña</h1>
    <p class="intro">Ingresá tu correo y te enviaremos un enlace de un solo uso.</p>
    <form method="post" action="{{ route('password.email') }}" class="form-stack">
        @csrf
        <label for="email">Correo electrónico</label>
        <input id="email" name="email" type="email" autocomplete="email" value="{{ old('email') }}" required autofocus>
        @error('email')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        <button class="button" type="submit">Enviar enlace</button>
    </form>
</section>
@endsection
