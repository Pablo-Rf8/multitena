@extends('layout')

@section('title', 'Nuevo administrador · Multitena')

@section('content')
<section class="panel form-panel">
    <a class="back-link" href="{{ route('dashboard') }}">← Volver al panel</a>
    <p class="eyebrow">Tu empresa</p>
    <h1>Agregar administrador</h1>
    <p class="intro">Este usuario podrá entrar y consultar solo su sucursal asignada.</p>
    <form method="post" action="{{ route('branch-users.store') }}" class="form-stack">
        @csrf
        <label for="name">Nombre</label>
        <input id="name" name="name" type="text" maxlength="120" value="{{ old('name') }}" required autofocus>
        @error('name') <p class="field-error" role="alert">{{ $message }}</p> @enderror

        <label for="email">Correo electrónico</label>
        <input id="email" name="email" type="email" maxlength="255" value="{{ old('email') }}" required>
        @error('email') <p class="field-error" role="alert">{{ $message }}</p> @enderror

        <label for="branch_id">Sucursal</label>
        <select id="branch_id" name="branch_id" required>
            <option value="">Seleccioná una sucursal</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>{{ $branch->name }}</option>
            @endforeach
        </select>
        @error('branch_id') <p class="field-error" role="alert">{{ $message }}</p> @enderror

        <label for="password">Contraseña inicial</label>
        <input id="password" name="password" type="password" minlength="12" autocomplete="new-password" required>
        <p class="hint">Mínimo 12 caracteres. Compartila de forma segura.</p>
        @error('password') <p class="field-error" role="alert">{{ $message }}</p> @enderror

        <label for="password_confirmation">Confirmar contraseña</label>
        <input id="password_confirmation" name="password_confirmation" type="password" minlength="12" autocomplete="new-password" required>
        <button class="button" type="submit">Crear administrador</button>
    </form>
</section>
@endsection
