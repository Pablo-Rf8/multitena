@extends('layout')
@section('title', 'Personal y acceso')
@section('content')
<section class="panel">
    <div class="section-head">
        <div>
            <p class="eyebrow">Equipo</p>
            <h1>Personal y acceso</h1>
            <p class="intro">Revisá solicitudes y administrá a las personas de tu empresa.</p>
        </div>
        <a class="button button-primary" href="{{ route('workforce.employees.create') }}"><svg class="icon" aria-hidden="true"><use href="#icon-plus"/></svg>Registrar empleado</a>
    </div>
    @if($errors->any())<div class="notice notice-error" role="alert">{{ $errors->first() }}</div>@endif
    <div class="workforce-list">
        @forelse($employees as $employee)
            <article class="workforce-item">
                <div class="workforce-person">
                    <div><strong>{{ $employee->name }}</strong><p class="muted">{{ $employee->email }}@if($employee->phone) · {{ $employee->phone }}@endif</p></div>
                    <span @class(['tag', 'tag-muted' => $employee->approval_status !== 'pending' && $employee->is_active])>{{ ! $employee->is_active ? 'Suspendido' : (['pending' => 'Pendiente', 'approved' => 'Aprobado', 'rejected' => 'Rechazado'][$employee->approval_status] ?? $employee->approval_status) }}</span>
                </div>
                <p class="muted">{{ $employee->category?->name ?? 'Sin categoría' }}</p>
                <div class="actions">
                    <a class="button button-ghost" href="{{ route('workforce.employees.edit', $employee) }}">Editar</a>
                    @if($employee->approval_status === 'pending')
                        <details class="inline-disclosure">
                            <summary>Revisar solicitud</summary>
                            <form method="post" action="{{ route('workforce.review', $employee) }}">
                                @csrf @method('PATCH')
                                <label>Categoría
                                    <select name="category_id"><option value="">Sin categoría</option>@foreach($categories->where('is_active', true) as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select>
                                </label>
                                <div class="actions"><button name="decision" value="approved">Aprobar</button><button name="decision" value="rejected" data-confirm="¿Rechazar esta solicitud?">Rechazar</button></div>
                            </form>
                        </details>
                    @endif
                    <form method="post" action="{{ route('workforce.employees.status', $employee) }}" class="inline-form" data-confirm="{{ $employee->is_active ? '¿Suspender el acceso de este empleado?' : '¿Reactivar el acceso de este empleado?' }}">
                        @csrf @method('PATCH')
                        <button name="is_active" value="{{ $employee->is_active ? 0 : 1 }}" class="text-button">{{ $employee->is_active ? 'Suspender' : 'Reactivar' }}</button>
                    </form>
                </div>
            </article>
        @empty
            <p class="empty-state">Todavía no hay empleados. Registrá el primero para comenzar.</p>
        @endforelse
    </div>
    {{ $employees->links() }}
</section>

<section class="panel advanced-sections" aria-label="Administración de acceso">
    <h2>Más opciones</h2>
    <details class="disclosure" id="categories">
        <summary>Categorías y permisos <small>{{ $categories->count() }} {{ $categories->count() === 1 ? 'categoría' : 'categorías' }}</small></summary>
        <div class="disclosure-body">
            <p class="muted">Definí qué puede consultar o editar cada categoría.</p>
            <form method="post" action="{{ route('workforce.categories') }}" class="form-stack compact-form">
                @csrf
                <label for="category-name">Nueva categoría</label><input id="category-name" name="name" required maxlength="100" placeholder="Ej. Repartidor">
                <div class="permission-list">
                    @foreach(['contacts.read' => 'Ver contactos', 'contacts.write' => 'Editar contactos', 'opportunities.read' => 'Ver oportunidades', 'opportunities.write' => 'Editar oportunidades'] as $permission => $label)
                        <label><input type="checkbox" name="permissions[]" value="{{ $permission }}"> {{ $label }}</label>
                    @endforeach
                </div>
                <button type="submit">Crear categoría</button>
            </form>
            <div class="workforce-list">
                @foreach($categories as $category)
                    <details class="inline-disclosure workforce-item">
                        <summary>{{ $category->name }} <span class="muted">{{ $category->is_active ? 'Activa' : 'Desactivada' }}</span></summary>
                        <form method="post" action="{{ route('workforce.categories.update', $category) }}" class="form-stack compact-form">
                            @csrf @method('PUT')
                            <label>Nombre <input name="name" value="{{ $category->name }}" required maxlength="100"></label>
                            <div class="permission-list">
                                @foreach(['contacts.read' => 'Ver contactos', 'contacts.write' => 'Editar contactos', 'opportunities.read' => 'Ver oportunidades', 'opportunities.write' => 'Editar oportunidades'] as $permission => $label)
                                    <label><input type="checkbox" name="permissions[]" value="{{ $permission }}" @checked(in_array($permission, $category->permissions ?? [], true))> {{ $label }}</label>
                                @endforeach
                            </div>
                            <label>Estado <select name="is_active"><option value="1" @selected($category->is_active)>Activa</option><option value="0" @selected(!$category->is_active)>Desactivada</option></select></label>
                            <button type="submit">Guardar cambios</button>
                        </form>
                        <form method="post" action="{{ route('workforce.categories.delete', $category) }}" class="inline-form" data-confirm="¿Eliminar esta categoría? Solo se puede eliminar si no tiene empleados asignados.">
                            @csrf @method('DELETE')<button type="submit" class="text-button">Eliminar categoría</button>
                        </form>
                    </details>
                @endforeach
            </div>
        </div>
    </details>
    <details class="disclosure" id="settings">
        <summary>Configuración de acceso</summary>
        <div class="disclosure-body">
            <form method="post" action="{{ route('workforce.settings') }}" class="form-stack compact-form">
                @csrf @method('PUT')
                <label>Dominios de correo autorizados, separados por coma <input name="email_domains" value="{{ implode(', ', $settings->email_domains ?? []) }}" placeholder="empresa.com"></label>
                <label>Acceso de empleados <select name="employee_access_enabled"><option value="1" @selected($settings->employee_access_enabled)>Habilitado</option><option value="0" @selected(!$settings->employee_access_enabled)>Deshabilitado</option></select></label>
                <label>Aprobación de nuevos dispositivos <select name="require_device_approval"><option value="0" @selected(!$settings->require_device_approval)>No</option><option value="1" @selected($settings->require_device_approval)>Sí</option></select></label>
                <label>Duración de sesión móvil (horas) <input type="number" name="token_lifetime_hours" min="1" max="2160" value="{{ $settings->token_lifetime_hours ?? 720 }}"></label>
                <button type="submit">Guardar configuración</button>
            </form>
        </div>
    </details>
    <details class="disclosure" id="devices">
        <summary>Dispositivos <small>{{ $devices->where('status', 'pending')->count() }} {{ $devices->where('status', 'pending')->count() === 1 ? 'pendiente' : 'pendientes' }}</small></summary>
        <div class="disclosure-body workforce-list">
            @forelse($devices as $device)
                <div class="workforce-item"><strong>{{ $device->name }}</strong><p class="muted">Usuario #{{ $device->user_id }} · {{ ['pending' => 'Pendiente', 'approved' => 'Autorizado', 'revoked' => 'Revocado'][$device->status] ?? $device->status }}</p>
                    @if($device->status !== 'revoked')<form method="post" action="{{ route('workforce.device', $device) }}" class="actions">@csrf @method('PATCH')
                        @if($device->status === 'pending')<button name="status" value="approved">Autorizar</button>@endif
                        <button name="status" value="revoked" data-confirm="¿Revocar este dispositivo y sus sesiones activas?">Revocar</button>
                    </form>@endif
                </div>
            @empty<p class="empty-state">Aún no hay dispositivos registrados.</p>@endforelse
        </div>
    </details>
    <details class="disclosure" id="sessions">
        <summary>Sesiones web y móviles <small>{{ $sessions->where('status', 'active')->count() }} {{ $sessions->where('status', 'active')->count() === 1 ? 'activa' : 'activas' }}</small></summary>
        <div class="disclosure-body workforce-list">
            @forelse($sessions as $accessSession)
                <div class="workforce-item"><strong>{{ $accessSession->user?->name ?? 'Usuario eliminado' }}</strong><p class="muted">{{ $accessSession->channel === 'web' ? 'Navegador web' : ($accessSession->device?->name ?? 'Móvil') }} · {{ $accessSession->status === 'active' ? 'Activa' : 'Finalizada' }}</p>
                    <p class="muted">Inicio: {{ $accessSession->started_at }} · Último acceso: {{ $accessSession->last_seen_at ?? '—' }} @if($accessSession->ended_at)· Cierre: {{ $accessSession->ended_at }}@endif @if($accessSession->end_reason)· Motivo: {{ $accessSession->end_reason }}@endif</p>
                    @if($accessSession->status === 'active')<form method="post" action="{{ route('workforce.sessions.end', $accessSession) }}" data-confirm="¿Finalizar esta sesión?">@csrf @method('PATCH')<button type="submit">Finalizar sesión</button></form>@endif
                </div>
            @empty<p class="empty-state">Aún no hay sesiones.</p>@endforelse
        </div>
    </details>
</section>
@endsection
