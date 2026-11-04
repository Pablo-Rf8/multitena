@extends('layout')
@section('title', __('Editar empleado'))
@section('content')
<section class="panel narrow"><p class="eyebrow">{{ __('Personal') }}</p><h1>{{ __('Editar empleado') }}</h1>
@if($errors->any())<div class="notice notice-error">{{ $errors->first() }}</div>@endif
<form method="post" action="{{ route('workforce.employees.update', $employee) }}" class="form-stack">@csrf @method('PUT')
<label for="name">{{ __('Nombre') }}</label><input id="name" name="name" value="{{ old('name', $employee->name) }}" required>
<label for="email">{{ __('Correo') }}</label><input id="email" type="email" name="email" value="{{ old('email', $employee->email) }}" required>
<label for="phone">{{ __('Teléfono (opcional)') }}</label><input id="phone" type="tel" name="phone" maxlength="40" autocomplete="tel" value="{{ old('phone', $employee->phone) }}">
<label for="branch_id">{{ __('Sucursal') }}</label><select id="branch_id" name="branch_id">@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected(old('branch_id', $employee->branch_id) == $branch->id)>{{ $branch->name }}</option>@endforeach</select>
<label for="category_id">{{ __('Categoría') }}</label><select id="category_id" name="category_id"><option value="">{{ __('Sin categoría') }}</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id', $employee->category_id) == $category->id)>{{ $category->name }}</option>@endforeach</select>
<label for="is_active">{{ __('Acceso') }}</label><select id="is_active" name="is_active"><option value="1" @selected(old('is_active', $employee->is_active) == 1)>{{ __('Activo') }}</option><option value="0" @selected(old('is_active', $employee->is_active) == 0)>{{ __('Suspendido') }}</option></select>
<button class="button button-primary">{{ __('Guardar empleado') }}</button></form><p><a class="back-link" href="{{ route('workforce.index') }}">← {{ __('Volver a personal') }}</a></p></section>
@endsection
