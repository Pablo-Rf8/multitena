@extends('layout')

@section('title', 'Cambiar contraseña · Multitena')

@section('content')
<section class="panel form-panel">
    <a class="back-link" href="{{ route('dashboard') }}">← Volver al panel</a>
    <p class="eyebrow">Tu cuenta</p>
    <h1>Cambiar contraseña</h1>
    <form method="post" action="{{ route('password.update') }}" class="form-stack">
        @csrf
        @method('PUT')
        <label for="current_password">Contraseña actual</label>
        <input id="current_password" name="current_password" type="password" autocomplete="current-password" required autofocus>
        @error('current_password') <p class="field-error" role="alert">{{ $message }}</p> @enderror

        <label for="password">Nueva contraseña</label>
        <input id="password" name="password" type="password" minlength="12" autocomplete="new-password" required>
        @error('password') <p class="field-error" role="alert">{{ $message }}</p> @enderror

        <label for="password_confirmation">Confirmar nueva contraseña</label>
        <input id="password_confirmation" name="password_confirmation" type="password" minlength="12" autocomplete="new-password" required>
        <button class="button" type="submit">Guardar contraseña</button>
    </form>
</section>
@endsection
