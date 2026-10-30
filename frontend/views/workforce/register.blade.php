@extends('layout')
@section('title', 'Registrar empleado')
@section('content')
<section class="panel narrow"><p class="eyebrow">Personal</p><h1>Registrar empleado</h1><p class="intro">El acceso quedará pendiente de aprobación.</p>
@if($errors->any())<div class="notice notice-error">{{ $errors->first() }}</div>@endif
<form method="post" action="{{ route('workforce.employees.store') }}" class="form-stack">@csrf
<label for="name">Nombre</label><input id="name" name="name" value="{{ old('name') }}" required>
<label for="email">Correo</label><input id="email" type="email" name="email" value="{{ old('email') }}" required>
<label for="phone">Teléfono (opcional)</label><input id="phone" type="tel" name="phone" maxlength="40" autocomplete="tel" value="{{ old('phone') }}">
<label for="branch_id">Sucursal</label><select id="branch_id" name="branch_id">@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select>
<label for="password">Contraseña temporal</label><input id="password" type="password" name="password" minlength="12" required>
<label for="password_confirmation">Confirmar contraseña</label><input id="password_confirmation" type="password" name="password_confirmation" minlength="12" required>
<button class="button">Registrar</button></form><p><a href="{{ route('workforce.index') }}">Volver</a></p></section>
@endsection
