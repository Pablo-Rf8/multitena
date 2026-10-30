@extends('layout')
@section('title', 'Registrar empleados')
@section('content')
<section class="panel">
<h1>Registrar empleado</h1>
<p>El empleado quedará pendiente de aprobación por su empresa.</p>
@if ($errors->any()) <div class="notice notice-error">{{ $errors->first() }}</div> @endif
<form method="post" action="{{ route('employees.store') }}">
@csrf
<label>Empresa <select name="company_id" required>@foreach($companies as $company)<option value="{{ $company->id }}">{{ $company->name }} ({{ $company->code ?? $company->slug }})</option>@endforeach</select></label>
<label>Nombre <input name="name" required maxlength="120" value="{{ old('name') }}"></label>
<label>Correo <input type="email" name="email" required value="{{ old('email') }}"></label>
<label>Teléfono (opcional) <input type="tel" name="phone" maxlength="40" autocomplete="tel" value="{{ old('phone') }}"></label>
<label>Contraseña <input type="password" name="password" required minlength="12"></label>
<label>Confirmar contraseña <input type="password" name="password_confirmation" required minlength="12"></label>
<button type="submit">Crear empleado</button>
</form>
</section>
<section class="panel">
<h2>Importar empleados CSV</h2>
<p>Archivo de hasta 1 MB y 1000 filas. Encabezado: <code>name,email,password</code>. Cada error se informa con su número de fila.</p>
<form method="post" action="{{ route('employees.import') }}" enctype="multipart/form-data">
@csrf
<label>Empresa <select name="company_id" required>@foreach($companies as $company)<option value="{{ $company->id }}">{{ $company->name }}</option>@endforeach</select></label>
<label>Archivo CSV <input type="file" name="file" accept=".csv,text/csv" required></label>
<button type="submit">Importar</button>
</form>
@if(session('import_errors'))<h3>Errores de importación</h3><ul>@foreach(session('import_errors') as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
</section>
@endsection
