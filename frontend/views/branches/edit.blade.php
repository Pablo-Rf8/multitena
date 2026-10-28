@extends('layout')

@section('title', 'Editar sucursal · Multitena')

@section('content')
<section class="panel form-panel">
    <a class="back-link" href="{{ route('dashboard') }}">← Volver al panel</a>
    <p class="eyebrow">Tu empresa</p>
    <h1>Editar {{ $branch->name }}</h1>
    <form method="post" action="{{ route('branches.update', $branch) }}" class="form-stack">
        @csrf @method('PUT')
        <label for="name">Nombre</label>
        <input id="name" name="name" value="{{ old('name', $branch->name) }}" maxlength="120" required autofocus @readonly($branch->is_central)>
        @if($branch->is_central)<p class="hint">La sucursal inicial conserva el nombre Central.</p>@endif
        @error('name')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        <label for="address">Dirección</label>
        <input id="address" name="address" value="{{ old('address', $branch->address) }}" maxlength="255">
        @error('address')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        <label for="phone">Teléfono</label>
        <input id="phone" name="phone" value="{{ old('phone', $branch->phone) }}" maxlength="40">
        @error('phone')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        <button class="button" type="submit">Guardar sucursal</button>
    </form>
</section>
@endsection
