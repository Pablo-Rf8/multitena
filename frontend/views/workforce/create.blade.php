@extends('layout')
@section('title', __('Registrar empleados'))
@section('content')
<section class="panel">
<h1>{{ __('Registrar empleado') }}</h1>
<p>{{ __('El empleado quedará pendiente de aprobación por su empresa.') }}</p>
@if ($errors->any()) <div class="notice notice-error">{{ $errors->first() }}</div> @endif
<form method="post" action="{{ route('employees.store') }}">
@csrf
<label>{{ __('Empresa') }} <select name="company_id" required>@foreach($companies as $company)<option value="{{ $company->id }}" @selected((string) old('company_id', request('company_id')) === (string) $company->id)>{{ $company->name }} ({{ $company->code ?? $company->slug }})</option>@endforeach</select></label>
<label>{{ __('Nombre') }} <input name="name" required maxlength="120" value="{{ old('name') }}"></label>
<label>{{ __('Correo') }} <input type="email" name="email" required value="{{ old('email') }}"></label>
<label>{{ __('Teléfono (opcional)') }} <input type="tel" name="phone" maxlength="40" autocomplete="tel" value="{{ old('phone') }}"></label>
<label>{{ __('Contraseña') }} <input type="password" name="password" required minlength="12"></label>
<label>{{ __('Confirmar contraseña') }} <input type="password" name="password_confirmation" required minlength="12"></label>
<button class="button button-primary" type="submit">{{ __('Crear empleado') }}</button>
</form>
</section>
<section class="panel">
<h2>{{ __('Importar empleados CSV') }}</h2>
<p>{{ __('Archivo de hasta 1 MB y 1000 filas. Encabezado:') }} <code>name,email,password</code>{{ __('. Cada error se informa con su número de fila.') }}</p>
<form method="post" action="{{ route('employees.import') }}" enctype="multipart/form-data">
@csrf
<label>{{ __('Empresa') }} <select name="company_id" required>@foreach($companies as $company)<option value="{{ $company->id }}" @selected((string) old('company_id', request('company_id')) === (string) $company->id)>{{ $company->name }}</option>@endforeach</select></label>
<label>{{ __('Archivo CSV') }} <input type="file" name="file" accept=".csv,text/csv" required></label>
<button type="submit">{{ __('Importar') }}</button>
</form>
@if(session('import_errors'))<h3>{{ __('Errores de importación') }}</h3><ul>@foreach(session('import_errors') as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
</section>
@endsection
