@extends('layout')
@section('title', 'Historial de códigos · Multitena')
@section('content')
<section class="panel">
    <a class="back-link" href="{{ route('codes.admin') }}">← Volver a gestión de códigos</a>
    <p class="eyebrow">Superadministración</p><h1>Historial de inicios con código</h1>
    <form method="get" action="{{ route('codes.history') }}" class="filter-bar">
        <select name="company_id" aria-label="Empresa"><option value="">Todas las empresas</option>@foreach($companies as $company)<option value="{{ $company->id }}" @selected(request('company_id') == $company->id)>{{ $company->name }}</option>@endforeach</select>
        <select name="branch_id" aria-label="Sucursal"><option value="">Todas las sucursales</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected(request('branch_id') == $branch->id)>{{ $branch->name }}</option>@endforeach</select>
        <select name="user_id" aria-label="Usuario"><option value="">Todos los usuarios</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>{{ $user->name }}</option>@endforeach</select>
        <select name="role" aria-label="Rol"><option value="">Todos los roles</option>@foreach(['company_admin'=>'Administrador de empresa','branch_admin'=>'Administrador de sucursal','employee'=>'Empleado'] as $key=>$name)<option value="{{ $key }}" @selected(request('role') === $key)>{{ $name }}</option>@endforeach</select>
        <select name="result" aria-label="Resultado"><option value="">Todos los resultados</option><option value="success" @selected(request('result') === 'success')>Exitoso</option><option value="failed" @selected(request('result') === 'failed')>Fallido</option></select>
        <label>Desde <input type="date" name="from" value="{{ request('from') }}"></label><label>Hasta <input type="date" name="to" value="{{ request('to') }}"></label>
        <button class="button button-secondary">Filtrar</button>
    </form>
    <div class="table-scroll"><table><thead><tr><th>Fecha y hora</th><th>Usuario</th><th>Rol</th><th>Empresa y sucursal</th><th>IP</th><th>Resultado</th><th>Motivo</th><th>Sesión</th></tr></thead><tbody>
        @forelse($attempts as $attempt)<tr><td>{{ $attempt->created_at->format('d/m/Y H:i:s') }}</td><td>{{ $attempt->user?->name ?? 'No identificado' }}</td><td>{{ $attempt->role ?? '—' }}</td><td>{{ $attempt->company?->name ?? '—' }} · {{ $attempt->branch?->name ?? '—' }}</td><td>{{ $attempt->ip_address }}</td><td>{{ $attempt->result === 'success' ? 'Exitoso' : 'Fallido' }}</td><td>{{ $attempt->reason ?? '—' }}</td><td>{{ $attempt->session_hash ? substr($attempt->session_hash, 0, 12).'…' : '—' }}</td></tr>
        @empty<tr><td colspan="8">Todavía no hay intentos registrados.</td></tr>@endforelse
    </tbody></table></div>{{ $attempts->links() }}
</section>
<section class="panel code-section"><h2>Acciones sobre enlaces</h2><div class="table-scroll"><table><thead><tr><th>Fecha</th><th>Acción</th><th>Administrador</th><th>Usuario</th><th>Empresa</th></tr></thead><tbody>@forelse($events as $event)<tr><td>{{ $event->created_at->format('d/m/Y H:i:s') }}</td><td>{{ $event->event }}</td><td>{{ $event->actor?->name ?? 'Sistema' }}</td><td>#{{ $event->properties['user_id'] ?? '—' }}</td><td>#{{ $event->company_id }}</td></tr>@empty<tr><td colspan="5">Sin acciones registradas.</td></tr>@endforelse</tbody></table></div></section>
@endsection
