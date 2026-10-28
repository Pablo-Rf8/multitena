@extends('layout')

@section('title', 'Nueva sucursal · Multitena')

@section('content')
<section class="panel form-panel">
    <a class="back-link" href="{{ route('dashboard') }}">← Volver al panel</a>
    <p class="eyebrow">Tu empresa</p>
    <h1>Agregar sucursal</h1>
    <p class="intro">La sucursal quedará asociada únicamente a tu empresa.</p>
    <form method="post" action="{{ route('branches.store') }}" class="form-stack">
        @csrf
        <label for="name">Nombre de la sucursal</label>
        <input id="name" name="name" type="text" maxlength="120" value="{{ old('name') }}" required autofocus>
        @error('name') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        <label for="address">Dirección (opcional)</label>
        <input id="address" name="address" value="{{ old('address') }}" maxlength="255">
        @error('address') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        <label for="phone">Teléfono (opcional)</label>
        <input id="phone" name="phone" value="{{ old('phone') }}" maxlength="40">
        @error('phone') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        <button class="button" type="submit">Guardar sucursal</button>
    </form>
</section>
@endsection
