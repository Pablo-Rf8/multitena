@extends('layout')
@section('title', 'Personal y acceso')
@section('content')
<h1>Personal y acceso</h1>
@if ($errors->any()) <div class="notice notice-error">{{ $errors->first() }}</div> @endif
<section class="panel"><h2>Empleados</h2>
@foreach($employees as $employee)
<article><strong>{{ $employee->name }}</strong> — {{ $employee->email }} — {{ $employee->approval_status }} — {{ $employee->category?->name ?? 'Sin categoría' }}
@if($employee->approval_status === 'pending')
<form method="post" action="{{ route('workforce.review', $employee) }}">@csrf @method('PATCH')
<label>Categoría <select name="category_id"><option value="">Sin categoría</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select></label>
<button name="decision" value="approved">Aprobar</button><button name="decision" value="rejected">Rechazar</button>
</form>@endif</article>
@if($employee->approval_status === 'approved')
<form method="post" action="{{ route('workforce.assign-category', $employee) }}">@csrf @method('PATCH')
<label>Cambiar categoría <select name="category_id"><option value="">Sin categoría</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected($employee->category_id === $category->id)>{{ $category->name }}</option>@endforeach</select></label><button>Guardar</button></form>
@endif
@endforeach
{{ $employees->links() }}
</section>
<section class="panel"><h2>Categorías y permisos</h2>
@foreach($categories as $category)<p>{{ $category->name }}: {{ implode(', ', $category->permissions ?? []) }}</p>@endforeach
<form method="post" action="{{ route('workforce.categories') }}">@csrf
<label>Nombre <input name="name" required maxlength="100" placeholder="Repartidor"></label>
@foreach(['contacts.read', 'contacts.write', 'opportunities.read', 'opportunities.write'] as $permission)
<label><input type="checkbox" name="permissions[]" value="{{ $permission }}"> {{ $permission }}</label>
@endforeach
<button>Crear categoría</button></form></section>
<section class="panel"><h2>Configuración de acceso</h2>
<form method="post" action="{{ route('workforce.settings') }}">@csrf @method('PUT')
<label>Dominios de correo autorizados, separados por coma <input name="email_domains" value="{{ implode(', ', $settings->email_domains ?? []) }}" placeholder="empresa.com"></label>
<label>Acceso de empleados <select name="employee_access_enabled"><option value="1" @selected($settings->employee_access_enabled)>Habilitado</option><option value="0" @selected(!$settings->employee_access_enabled)>Deshabilitado</option></select></label>
<label>Aprobación de nuevos dispositivos <select name="require_device_approval"><option value="0" @selected(!$settings->require_device_approval)>No</option><option value="1" @selected($settings->require_device_approval)>Sí</option></select></label>
<label>Duración de token (horas) <input type="number" name="token_lifetime_hours" min="1" max="2160" value="{{ $settings->token_lifetime_hours ?? 720 }}"></label>
<button>Guardar</button></form></section>
<section class="panel"><h2>Dispositivos</h2>
@forelse($devices as $device)
<p>{{ $device->name }} — usuario #{{ $device->user_id }} — {{ $device->status }}
@if($device->status !== 'revoked')<form method="post" action="{{ route('workforce.device', $device) }}">@csrf @method('PATCH')
@if($device->status === 'pending')<button name="status" value="approved">Autorizar</button>@endif
<button name="status" value="revoked">Revocar</button></form>@endif</p>
@empty <p>Sin dispositivos.</p> @endforelse</section>
<section class="panel"><h2>Sesiones móviles</h2>
@forelse($sessions as $mobileSession)<p>{{ $mobileSession->user->name }} · Dispositivo #{{ $mobileSession->device_id }} · Inicio: {{ $mobileSession->started_at }} · Estado: {{ $mobileSession->status }} · Motivo: {{ $mobileSession->end_reason ?? '—' }}</p>
@empty <p>Sin sesiones.</p> @endforelse</section>
@endsection
