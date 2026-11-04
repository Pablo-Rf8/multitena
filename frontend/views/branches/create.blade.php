@extends('layout')

@section('title', __('Nueva sucursal').' · Multitena')

@section('content')
<section class="panel form-panel">
    <a class="back-link" href="{{ route('dashboard') }}">← {{ __('Volver al panel') }}</a>
    <p class="eyebrow">{{ __('Tu empresa') }}</p>
    <h1>{{ __('Agregar sucursal') }}</h1>
    <p class="intro">{{ __('La sucursal quedará asociada únicamente a tu empresa.') }}</p>
    <form method="post" action="{{ route('branches.store') }}" class="form-stack">
        @csrf
        <label for="name">{{ __('Nombre de la sucursal') }}</label>
        <input id="name" name="name" type="text" maxlength="120" value="{{ old('name') }}" required autofocus>
        @error('name') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        <label for="address">{{ __('Dirección (opcional)') }}</label>
        <input id="address" name="address" value="{{ old('address') }}" maxlength="255">
        @error('address') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        <label for="phone">{{ __('Teléfono (opcional)') }}</label>
        <input id="phone" name="phone" value="{{ old('phone') }}" maxlength="40">
        @error('phone') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        <button class="button button-primary" type="submit">{{ __('Guardar sucursal') }}</button>
    </form>
</section>
@endsection
