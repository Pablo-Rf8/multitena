@extends('layout')

@section('title', 'Editar usuario · Multitena')

@section('content')
<section class="panel form-panel">
    <a class="back-link" href="{{ route('dashboard') }}">← Volver al panel</a>
    <p class="eyebrow">Administrador de sucursal</p>
    <h1>Editar {{ $managedUser->name }}</h1>
    <form method="post" action="{{ route('branch-users.update', $managedUser) }}" class="form-stack">
        @csrf @method('PUT')
        <label for="name">Nombre</label>
        <input id="name" name="name" value="{{ old('name', $managedUser->name) }}" maxlength="120" required autofocus>
        @error('name')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        <label for="email">Correo</label>
        <input id="email" name="email" type="email" value="{{ old('email', $managedUser->email) }}" maxlength="255" required>
        @error('email')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        <label for="branch_id">Sucursal</label>
        <select id="branch_id" name="branch_id" required>
            @foreach($branches as $branch)
                <option value="{{ $branch->id }}" @selected(old('branch_id', $managedUser->branch_id) == $branch->id)>{{ $branch->name }}</option>
            @endforeach
        </select>
        @error('branch_id')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        <label for="password">Nueva contraseña (opcional)</label>
        <input id="password" name="password" type="password" minlength="12" autocomplete="new-password">
        <p class="hint">Dejá este campo vacío para conservar la contraseña actual.</p>
        @error('password')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        <label for="password_confirmation">Confirmar contraseña</label>
        <input id="password_confirmation" name="password_confirmation" type="password" minlength="12" autocomplete="new-password">
        <button class="button" type="submit">Guardar usuario</button>
    </form>
</section>
@endsection
