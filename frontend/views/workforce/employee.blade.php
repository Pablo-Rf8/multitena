@extends('layout')
@section('title', 'Editar empleado')
@section('content')
<section class="panel narrow"><p class="eyebrow">Personal</p><h1>Editar empleado</h1>
@if($errors->any())<div class="notice notice-error">{{ $errors->first() }}</div>@endif
<form method="post" action="{{ route('workforce.employees.update', $employee) }}" class="form-stack">@csrf @method('PUT')
<label for="name">Nombre</label><input id="name" name="name" value="{{ old('name', $employee->name) }}" required>
<label for="email">Correo</label><input id="email" type="email" name="email" value="{{ old('email', $employee->email) }}" required>
<label for="branch_id">Sucursal</label><select id="branch_id" name="branch_id">@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected(old('branch_id', $employee->branch_id) == $branch->id)>{{ $branch->name }}</option>@endforeach</select>
<label for="category_id">Categoría</label><select id="category_id" name="category_id"><option value="">Sin categoría</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id', $employee->category_id) == $category->id)>{{ $category->name }}</option>@endforeach</select>
<label for="is_active">Acceso</label><select id="is_active" name="is_active"><option value="1" @selected(old('is_active', $employee->is_active) == 1)>Activo</option><option value="0" @selected(old('is_active', $employee->is_active) == 0)>Suspendido</option></select>
<button class="button">Guardar empleado</button></form><p><a href="{{ route('workforce.index') }}">Volver a personal</a></p></section>
@endsection
