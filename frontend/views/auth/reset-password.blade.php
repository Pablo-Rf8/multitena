@extends('layout')

@section('title', 'Nueva contraseña · Multitena')

@section('content')
<section class="panel narrow">
    <p class="eyebrow">Recuperar acceso</p>
    <h1>Elegí una nueva contraseña</h1>
    <form method="post" action="{{ route('password.store') }}" class="form-stack">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <label for="email">Correo electrónico</label>
        <input id="email" name="email" type="email" autocomplete="email" value="{{ old('email', $email) }}" required>
        @error('email')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        <label for="password">Nueva contraseña</label>
        <input id="password" name="password" type="password" minlength="12" autocomplete="new-password" required>
        @error('password')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        <label for="password_confirmation">Confirmar contraseña</label>
        <input id="password_confirmation" name="password_confirmation" type="password" minlength="12" autocomplete="new-password" required>
        <button class="button" type="submit">Guardar contraseña</button>
    </form>
</section>
@endsection
