@extends('layout')

@section('title', __('Cambiar contraseña').' · Multitena')

@section('content')
<section class="panel form-panel">
    <a class="back-link" href="{{ route('dashboard') }}">← {{ __('Volver al panel') }}</a>
    <p class="eyebrow">{{ __('Tu cuenta') }}</p>
    <h1>{{ __('Cambiar contraseña') }}</h1>
    <form method="post" action="{{ route('password.update') }}" class="form-stack">
        @csrf
        @method('PUT')
        <label for="current_password">{{ __('Contraseña actual') }}</label>
        <input id="current_password" name="current_password" type="password" autocomplete="current-password" required autofocus>
        @error('current_password', 'accountPassword') <p class="field-error" role="alert">{{ $message }}</p> @enderror

        <label for="password">{{ __('Nueva contraseña') }}</label>
        <input id="password" name="password" type="password" minlength="12" autocomplete="new-password" required>
        @error('password', 'accountPassword') <p class="field-error" role="alert">{{ $message }}</p> @enderror

        <label for="password_confirmation">{{ __('Confirmar nueva contraseña') }}</label>
        <input id="password_confirmation" name="password_confirmation" type="password" minlength="12" autocomplete="new-password" required>
        <button class="button button-primary" type="submit">{{ __('Guardar contraseña') }}</button>
    </form>
</section>
@endsection
