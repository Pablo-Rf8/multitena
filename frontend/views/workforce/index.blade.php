@extends('layout')
@section('title', 'Personal y acceso')
@section('content')
<section class="page-heading"><p class="eyebrow">Administración</p><h1>Personal y acceso</h1><p class="intro">Solicitudes, categorías, dispositivos y seguridad de tu empresa.</p></section>
@if ($errors->any()) <div class="notice notice-error">{{ $errors->first() }}</div> @endif
<section class="panel"><h2>Empleados</h2>
<div class="workforce-list">@forelse($employees as $employee)
<article class="workforce-item"><strong>{{ $employee->name }}</strong> · {{ $employee->email }} <span class="tag">{{ ['pending' => 'Pendiente', 'approved' => 'Aprobado', 'rejected' => 'Rechazado'][$employee->approval_status] ?? $employee->approval_status }}</span><span class="muted">{{ $employee->category?->name ?? 'Sin categoría' }}</span>
@if($employee->approval_status === 'pending')
<form method="post" action="{{ route('workforce.review', $employee) }}">@csrf @method('PATCH')
<label>Categoría <select name="category_id"><option value="">Sin categoría</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select></label>
<button name="decision" value="approved">Aprobar</button><button name="decision" value="rejected">Rechazar</button>
</form>@endif</article>
@if($employee->approval_status === 'approved')
<form method="post" action="{{ route('workforce.assign-category', $employee) }}">@csrf @method('PATCH')
<label>Cambiar categoría <select name="category_id"><option value="">Sin categoría</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected($employee->category_id === $category->id)>{{ $category->name }}</option>@endforeach</select></label><button>Guardar</button></form>
@endif
@empty <p class="empty-state">Todavía no hay empleados registrados en esta empresa.</p> @endforelse</div>
{{ $employees->links() }}
</section>
<div class="workforce-grid">
<section class="panel"><h2>Categorías y permisos</h2>
@forelse($categories as $category)<p class="workforce-item"><strong>{{ $category->name }}</strong><br><span class="muted">{{ implode(', ', $category->permissions ?? []) ?: 'Sin permisos de CRM' }}</span></p>@empty <p class="empty-state">Creá una categoría para asignar permisos a empleados.</p> @endforelse
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
</div>
<div class="workforce-grid">
<section class="panel"><h2>Dispositivos</h2>
@forelse($devices as $device)
<p>{{ $device->name }} — usuario #{{ $device->user_id }} — {{ $device->status }}
@if($device->status !== 'revoked')<form method="post" action="{{ route('workforce.device', $device) }}">@csrf @method('PATCH')
@if($device->status === 'pending')<button name="status" value="approved">Autorizar</button>@endif
<button name="status" value="revoked">Revocar</button></form>@endif</p>
@empty <p class="empty-state">Aún no hay dispositivos registrados.</p> @endforelse</section>
<section class="panel"><h2>Sesiones móviles</h2>
@forelse($sessions as $mobileSession)<p>{{ $mobileSession->user->name }} · Dispositivo #{{ $mobileSession->device_id }} · Inicio: {{ $mobileSession->started_at }} · Estado: {{ $mobileSession->status }} · Motivo: {{ $mobileSession->end_reason ?? '—' }}</p>
@empty <p class="empty-state">Aún no hay sesiones móviles.</p> @endforelse</section>
</div>
@endsection
