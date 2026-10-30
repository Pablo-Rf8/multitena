@extends('layout')
@section('title', 'Gestión de códigos y accesos · Multitena')
@section('content')
<section class="page-heading"><p class="eyebrow">Superadministración</p><h1>Gestión de códigos y accesos</h1><p class="intro">Emití enlaces individuales, controlá su vigencia y supervisá los inicios de sesión con código.</p></section>
<section class="panel">
    <div class="section-head"><h2>Enlaces emitidos</h2><a class="button button-secondary" href="{{ route('codes.history') }}">Ver historial de accesos</a></div>
    <form method="get" action="{{ route('codes.admin') }}" class="filter-bar">
        <label class="sr-only" for="codes-search">Usuario</label><input id="codes-search" name="q" value="{{ request('q') }}" placeholder="Buscar nombre o correo">
        <label class="sr-only" for="codes-company">Empresa</label><select id="codes-company" name="company_id"><option value="">Todas las empresas</option>@foreach($companies as $company)<option value="{{ $company->id }}" @selected(request('company_id') == $company->id)>{{ $company->name }}</option>@endforeach</select>
        <label class="sr-only" for="codes-branch">Sucursal</label><select id="codes-branch" name="branch_id"><option value="">Todas las sucursales</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected(request('branch_id') == $branch->id)>{{ $branch->name }} · {{ $branch->company?->name }}</option>@endforeach</select>
        <label class="sr-only" for="codes-role">Rol</label><select id="codes-role" name="role"><option value="">Todos los roles</option>@foreach(['company_admin'=>'Administrador de empresa','branch_admin'=>'Administrador de sucursal','employee'=>'Empleado'] as $key=>$name)<option value="{{ $key }}" @selected(request('role') === $key)>{{ $name }}</option>@endforeach</select>
        <label class="sr-only" for="codes-status">Estado</label><select id="codes-status" name="status"><option value="">Todos los estados</option>@foreach(['active'=>'Activo','expired'=>'Vencido','suspended'=>'Suspendido','revoked'=>'Revocado'] as $key=>$name)<option value="{{ $key }}" @selected(request('status') === $key)>{{ $name }}</option>@endforeach</select>
        <button class="button button-secondary">Filtrar</button>
    </form>
    @include('codes.partials.links', ['manage' => true, 'canClaim' => false])
</section>
<section class="panel code-section">
    <h2>Emitir enlace individual</h2>
    <p class="intro">Un enlace nuevo invalida el anterior. Se muestra una sola vez aquí y una vez al administrador de la empresa para su entrega.</p>
    <div class="table-scroll"><table><thead><tr><th>Usuario</th><th>Empresa</th><th>Rol</th><th>Acción</th></tr></thead><tbody>
        @forelse($users as $user)<tr><td>{{ $user->name }}<br><small>{{ $user->email }}</small></td><td>{{ $user->company?->name }} · {{ $user->branch?->name }}</td><td>{{ $user->role }}</td><td><form method="post" action="{{ route('codes.issue', $user) }}">@csrf<button class="text-button" type="submit">Generar enlace</button></form></td></tr>
        @empty<tr><td colspan="4">No hay usuarios para estos filtros.</td></tr>@endforelse
    </tbody></table></div>
    {{ $users->links() }}
</section>
<section class="panel code-section">
    <h2>Suspender una empresa</h2>
    <p>Esta acción suspende sus enlaces y finaliza las sesiones de los usuarios afectados.</p>
    <div class="code-actions">@foreach($companies as $company)<form method="post" action="{{ route('codes.suspend-company', $company) }}" data-confirm="¿Suspender todos los enlaces de {{ $company->name }}?">@csrf<button class="text-button" type="submit">{{ $company->name }}</button></form>@endforeach</div>
</section>
@endsection
