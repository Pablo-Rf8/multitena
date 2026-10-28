@extends('layout')

@section('title', ($managedUser ? 'Editar' : 'Nuevo').' administrador · Multitena')

@section('content')
<section class="panel form-panel">
    <a class="back-link" href="{{ route('companies.show', $company) }}">← Volver a la empresa</a>
    <p class="eyebrow">{{ $company->name }}</p>
    <h1>{{ $managedUser ? 'Editar administrador' : 'Agregar administrador' }}</h1>
    <p class="intro">Este rol puede administrar todas las sucursales y el CRM de la empresa.</p>
    <form method="post" action="{{ $managedUser ? route('company-admins.update', [$company, $managedUser]) : route('company-admins.store', $company) }}" class="form-stack">
        @csrf @if($managedUser) @method('PUT') @endif
        <label for="name">Nombre</label>
        <input id="name" name="name" value="{{ old('name', $managedUser?->name) }}" maxlength="120" required autofocus>
        @error('name')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        <label for="email">Correo</label>
        <input id="email" name="email" type="email" value="{{ old('email', $managedUser?->email) }}" maxlength="255" required>
        @error('email')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        <label for="branch_id">Sucursal asignada para ingreso</label>
        <select id="branch_id" name="branch_id" required>
            @foreach($branches as $branch)
                <option value="{{ $branch->id }}" @selected(old('branch_id', $managedUser?->branch_id) == $branch->id)>{{ $branch->name }}</option>
            @endforeach
        </select>
        @error('branch_id')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        <label for="password">{{ $managedUser ? 'Nueva contraseña (opcional)' : 'Contraseña inicial' }}</label>
        <input id="password" name="password" type="password" minlength="12" autocomplete="new-password" @required(! $managedUser)>
        <p class="hint">Mínimo 12 caracteres. Si cambiás la contraseña, compartila por un medio seguro.</p>
        @error('password')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        <label for="password_confirmation">Confirmar contraseña</label>
        <input id="password_confirmation" name="password_confirmation" type="password" minlength="12" autocomplete="new-password" @required(! $managedUser)>
        <button class="button" type="submit">{{ $managedUser ? 'Guardar cambios' : 'Crear administrador' }}</button>
    </form>
</section>
@endsection
