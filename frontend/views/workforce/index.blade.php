@extends('layout')
@section('title', __('Personal y acceso'))
@section('content')
<section class="panel">
    <div class="section-head">
        <div>
            <p class="eyebrow">{{ __('Equipo') }}</p>
            <h1>{{ __('Personal y acceso') }}</h1>
            <p class="intro">{{ __('Revisá solicitudes y administrá a las personas de tu empresa.') }}</p>
        </div>
        <a class="button button-primary" href="{{ route('workforce.employees.create') }}"><svg class="icon" aria-hidden="true"><use href="#icon-plus"/></svg>{{ __('Registrar empleado') }}</a>
    </div>
    @if($errors->any())<div class="notice notice-error" role="alert">{{ $errors->first() }}</div>@endif
    <div class="workforce-list">
        @forelse($employees as $employee)
            <article class="workforce-item">
                <div class="workforce-person">
                    <div><strong>{{ $employee->name }}</strong><p class="muted">{{ $employee->email }}@if($employee->phone) · {{ $employee->phone }}@endif</p></div>
                    <span @class(['tag', 'tag-muted' => $employee->approval_status !== 'pending' && $employee->is_active])>{{ ! $employee->is_active ? __('Suspendido') : (['pending' => __('Pendiente'), 'approved' => __('Aprobado'), 'rejected' => __('Rechazado')][$employee->approval_status] ?? $employee->approval_status) }}</span>
                </div>
                <p class="muted">{{ $employee->category?->name ?? __('Sin categoría') }}</p>
                <div class="actions">
                    <a class="button button-ghost button-sm" href="{{ route('workforce.employees.edit', $employee) }}">{{ __('Editar') }}</a>
                    @if($employee->approval_status === 'pending')
                        <details class="inline-disclosure">
                            <summary>{{ __('Revisar solicitud') }}</summary>
                            <form method="post" action="{{ route('workforce.review', $employee) }}">
                                @csrf @method('PATCH')
                                <label>{{ __('Categoría') }}
                                    <select name="category_id"><option value="">{{ __('Sin categoría') }}</option>@foreach($categories->where('is_active', true) as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select>
                                </label>
                                <div class="actions"><button class="button button-primary button-sm" name="decision" value="approved">{{ __('Aprobar') }}</button><button class="button button-danger button-sm" name="decision" value="rejected" data-confirm="{{ __('¿Rechazar esta solicitud?') }}">{{ __('Rechazar') }}</button></div>
                            </form>
                        </details>
                    @endif
                    <form method="post" action="{{ route('workforce.employees.status', $employee) }}" class="inline-form" data-confirm="{{ $employee->is_active ? __('¿Suspender el acceso de este empleado?') : __('¿Reactivar el acceso de este empleado?') }}">
                        @csrf @method('PATCH')
                        <button name="is_active" value="{{ $employee->is_active ? 0 : 1 }}" class="button button-ghost button-sm {{ $employee->is_active ? 'button-danger' : '' }}">{{ $employee->is_active ? __('Suspender') : __('Reactivar') }}</button>
                    </form>
                </div>
            </article>
        @empty
            <p class="empty-state">{{ __('Todavía no hay empleados. Registrá el primero para comenzar.') }}</p>
        @endforelse
    </div>
    {{ $employees->links() }}
</section>

<section class="panel advanced-sections" aria-label="{{ __('Administración de acceso') }}">
    <h2>{{ __('Más opciones') }}</h2>
    <details class="disclosure" id="categories">
        <summary>{{ __('Categorías y permisos') }} <small>{{ $categories->count() }} {{ $categories->count() === 1 ? __('categoría') : __('categorías') }}</small></summary>
        <div class="disclosure-body">
            <p class="muted">{{ __('Definí qué puede consultar o editar cada categoría.') }}</p>
            <form method="post" action="{{ route('workforce.categories') }}" class="form-stack compact-form">
                @csrf
                <label for="category-name">{{ __('Nueva categoría') }}</label><input id="category-name" name="name" required maxlength="100" placeholder="{{ __('Ej. Repartidor') }}">
                <div class="permission-list">
                    @foreach(['contacts.read' => __('Ver contactos'), 'contacts.write' => __('Editar contactos'), 'opportunities.read' => __('Ver oportunidades'), 'opportunities.write' => __('Editar oportunidades')] as $permission => $label)
                        <label><input type="checkbox" name="permissions[]" value="{{ $permission }}"> {{ __($label) }}</label>
                    @endforeach
                </div>
                <button type="submit">{{ __('Crear categoría') }}</button>
            </form>
            <div class="workforce-list">
                @foreach($categories as $category)
                    <details class="inline-disclosure workforce-item">
                        <summary>{{ $category->name }} <span class="muted">{{ $category->is_active ? __('Activa') : __('Desactivada') }}</span></summary>
                        <form method="post" action="{{ route('workforce.categories.update', $category) }}" class="form-stack compact-form">
                            @csrf @method('PUT')
                            <label>{{ __('Nombre') }} <input name="name" value="{{ $category->name }}" required maxlength="100"></label>
                            <div class="permission-list">
                                @foreach(['contacts.read' => __('Ver contactos'), 'contacts.write' => __('Editar contactos'), 'opportunities.read' => __('Ver oportunidades'), 'opportunities.write' => __('Editar oportunidades')] as $permission => $label)
                                    <label><input type="checkbox" name="permissions[]" value="{{ $permission }}" @checked(in_array($permission, $category->permissions ?? [], true))> {{ __($label) }}</label>
                                @endforeach
                            </div>
                            <label>{{ __('Estado') }} <select name="is_active"><option value="1" @selected($category->is_active)>{{ __('Activa') }}</option><option value="0" @selected(!$category->is_active)>{{ __('Desactivada') }}</option></select></label>
                            <button type="submit">{{ __('Guardar cambios') }}</button>
                        </form>
                        <form method="post" action="{{ route('workforce.categories.delete', $category) }}" class="inline-form" data-confirm="{{ __('¿Eliminar esta categoría? Solo se puede eliminar si no tiene empleados asignados.') }}">
                            @csrf @method('DELETE')<button type="submit" class="button button-danger button-sm">{{ __('Eliminar categoría') }}</button>
                        </form>
                    </details>
                @endforeach
            </div>
        </div>
    </details>
    <details class="disclosure" id="settings">
        <summary>{{ __('Configuración de acceso') }}</summary>
        <div class="disclosure-body">
            <form method="post" action="{{ route('workforce.settings') }}" class="form-stack compact-form">
                @csrf @method('PUT')
                <label>{{ __('Dominios de correo autorizados, separados por coma') }} <input name="email_domains" value="{{ implode(', ', $settings->email_domains ?? []) }}" placeholder="empresa.com"></label>
                <label>{{ __('Acceso de empleados') }} <select name="employee_access_enabled"><option value="1" @selected($settings->employee_access_enabled)>{{ __('Habilitado') }}</option><option value="0" @selected(!$settings->employee_access_enabled)>{{ __('Deshabilitado') }}</option></select></label>
                <label>{{ __('Aprobación de nuevos dispositivos') }} <select name="require_device_approval"><option value="0" @selected(!$settings->require_device_approval)>{{ __('No') }}</option><option value="1" @selected($settings->require_device_approval)>{{ __('Sí') }}</option></select></label>
                <label>{{ __('Duración de sesión móvil (horas)') }} <input type="number" name="token_lifetime_hours" min="1" max="2160" value="{{ $settings->token_lifetime_hours ?? 720 }}"></label>
                <button type="submit">{{ __('Guardar configuración') }}</button>
            </form>
        </div>
    </details>
    <details class="disclosure" id="devices">
        <summary>{{ __('Dispositivos') }} <small>{{ $devices->where('status', 'pending')->count() }} {{ $devices->where('status', 'pending')->count() === 1 ? __('pendiente') : __('pendientes') }}</small></summary>
        <div class="disclosure-body workforce-list">
            @forelse($devices as $device)
                <div class="workforce-item"><strong>{{ $device->name }}</strong><p class="muted">Usuario #{{ $device->user_id }} · {{ ['pending' => __('Pendiente'), 'approved' => __('Autorizado'), 'revoked' => __('Revocado')][$device->status] ?? $device->status }}</p>
                    @if($device->status !== 'revoked')<form method="post" action="{{ route('workforce.device', $device) }}" class="actions">@csrf @method('PATCH')
                        @if($device->status === 'pending')<button class="button button-primary button-sm" name="status" value="approved">{{ __('Autorizar') }}</button>@endif
                        <button class="button button-danger button-sm" name="status" value="revoked" data-confirm="{{ __('¿Revocar este dispositivo y sus sesiones activas?') }}">{{ __('Revocar') }}</button>
                    </form>@endif
                </div>
            @empty<p class="empty-state">{{ __('Aún no hay dispositivos registrados.') }}</p>@endforelse
        </div>
    </details>
    <details class="disclosure" id="sessions">
        <summary>{{ __('Sesiones web y móviles') }} <small>{{ $sessions->where('status', 'active')->count() }} {{ $sessions->where('status', 'active')->count() === 1 ? __('activa') : __('activas') }}</small></summary>
        <div class="disclosure-body workforce-list">
            @forelse($sessions as $accessSession)
                <div class="workforce-item"><strong>{{ $accessSession->user?->name ?? __('Usuario eliminado') }}</strong><p class="muted">{{ $accessSession->channel === 'web' ? __('Navegador web') : ($accessSession->device?->name ?? __('Móvil')) }} · {{ $accessSession->status === 'active' ? __('Activa') : __('Finalizada') }}</p>
                    <p class="muted">{{ __('Inicio:') }} {{ $accessSession->started_at }} · {{ __('Último acceso:') }} {{ $accessSession->last_seen_at ?? '—' }} @if($accessSession->ended_at)· {{ __('Cierre:') }} {{ $accessSession->ended_at }}@endif @if($accessSession->end_reason)· {{ __('Motivo:') }} {{ $accessSession->end_reason }}@endif</p>
                    @if($accessSession->status === 'active')<form method="post" action="{{ route('workforce.sessions.end', $accessSession) }}" data-confirm="{{ __('¿Finalizar esta sesión?') }}">@csrf @method('PATCH')<button class="button button-danger button-sm" type="submit">{{ __('Finalizar sesión') }}</button></form>@endif
                </div>
            @empty<p class="empty-state">{{ __('Aún no hay sesiones.') }}</p>@endforelse
        </div>
    </details>
</section>
@endsection
