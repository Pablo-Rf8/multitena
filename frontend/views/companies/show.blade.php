@extends('layout')

@section('title', $company->name.' · Multitena')

@section('content')
<section class="panel">
    <a class="back-link" href="{{ route('dashboard') }}">← Volver a empresas</a>
    <div class="section-head">
        <div>
            <p class="eyebrow">Empresa</p>
            <h1>{{ $company->name }} @if($company->is_demo)<span class="tag">DEMO</span>@endif</h1>
            <p class="intro">{{ $company->slug }} · {{ $company->is_active ? 'Activa' : 'Suspendida' }}</p>
        </div>
        <a class="button button-ghost" href="{{ route('companies.edit', $company) }}">Editar empresa</a>
    </div>
    <div class="stats">
        <div class="stat"><strong>{{ $company->branches->count() }}</strong><span>Sucursales</span></div>
        <div class="stat"><strong>{{ $userCount }}</strong><span>Usuarios</span></div>
        <div class="stat"><strong>{{ $contactCount }}</strong><span>Contactos</span></div>
        <div class="stat"><strong>{{ $opportunityCount }}</strong><span>Oportunidades</span></div>
    </div>
    <div class="dashboard-shortcuts"><a href="{{ route('companies.employees', $company) }}">Ver empleados <span aria-hidden="true">→</span></a><a href="{{ route('company-admins.create', $company) }}">Agregar administrador <span aria-hidden="true">→</span></a></div>
    <details class="disclosure"><summary>Información de la empresa</summary><div class="disclosure-body">
    <dl class="details">
        <div><dt>Código de empresa</dt><dd>{{ $company->code }}</dd></div>
        <div><dt>Dominios de acceso</dt><dd>{{ $domains ? implode(', ', $domains) : 'Sin configurar' }}</dd></div>
        <div><dt>Moneda</dt><dd>{{ $company->currency }}</dd></div>
        <div><dt>Correo de contacto</dt><dd>{{ $company->contact_email ?: 'Sin registrar' }}</dd></div>
        <div><dt>Teléfono</dt><dd>{{ $company->phone ?: 'Sin registrar' }}</dd></div>
    </dl>
    <form method="post" action="{{ route('companies.status', $company) }}" class="actions" data-confirm="{{ $company->is_active ? '¿Suspender la empresa y bloquear el acceso de todos sus usuarios?' : '¿Activar la empresa?' }}">
        @csrf @method('PATCH')
        <input type="hidden" name="is_active" value="{{ $company->is_active ? '0' : '1' }}">
        <button type="submit" class="button {{ $company->is_active ? 'button-danger' : '' }}">{{ $company->is_active ? 'Suspender empresa' : 'Activar empresa' }}</button>
    </form>
    </div></details>

    <details class="disclosure"><summary>Sucursales <small>{{ $company->branches->count() }} registradas</small></summary><div class="disclosure-body">
    <div class="table-scroll">
        <table>
            <thead><tr><th>Nombre</th><th>Estado</th><th>Dirección</th></tr></thead>
            <tbody>
                @foreach($company->branches as $branch)
                    <tr><td>{{ $branch->name }} @if($branch->is_central)<span class="tag">Central</span>@endif</td><td>{{ $branch->is_active ? 'Activa' : 'Suspendida' }}</td><td>{{ $branch->address ?: '—' }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
    </div></details>
    <details class="disclosure"><summary>Administradores <small>{{ $company->users->whereIn('role', ['company_admin', 'branch_admin'])->count() }} registrados</small></summary><div class="disclosure-body">
    <div class="section-head section-subhead">
        <h2>Administradores de empresa</h2>
        <a class="button button-ghost" href="{{ route('company-admins.create', $company) }}">Agregar administrador</a>
    </div>
    <div class="table-scroll">
        <table>
            <thead><tr><th>Nombre</th><th>Correo</th><th>Estado</th><th>Último ingreso</th><th>Acciones</th></tr></thead>
            <tbody>
                @foreach($company->users->where('role', 'company_admin') as $member)
                    <tr>
                        <td>{{ $member->name }}</td><td>{{ $member->email }}</td>
                        <td>{{ $member->is_active ? 'Activo' : 'Suspendido' }}</td>
                        <td>{{ $member->last_login_at?->format('d/m/Y H:i') ?? 'Nunca' }}</td>
                        <td>
                            <a href="{{ route('company-admins.edit', [$company, $member]) }}">Editar</a>
                            <form method="post" action="{{ route('company-admins.status', [$company, $member]) }}" class="inline-form" data-confirm="{{ $member->is_active ? '¿Suspender este administrador?' : '¿Activar este administrador?' }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="is_active" value="{{ $member->is_active ? '0' : '1' }}">
                                <button type="submit" class="text-button">{{ $member->is_active ? 'Suspender' : 'Activar' }}</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <h2>Administradores de sucursal</h2>
    <div class="table-scroll"><table><thead><tr><th>Nombre</th><th>Correo</th><th>Sucursal</th><th>Estado</th></tr></thead><tbody>
        @forelse($company->users->where('role', 'branch_admin') as $member)
            <tr><td>{{ $member->name }}</td><td>{{ $member->email }}</td><td>{{ $company->branches->firstWhere('id', $member->branch_id)?->name ?? '—' }}</td><td>{{ $member->is_active ? 'Activo' : 'Suspendido' }}</td></tr>
        @empty<tr><td colspan="4">Sin administradores de sucursal.</td></tr>@endforelse
    </tbody></table></div>
    </div></details>
    <details class="disclosure"><summary>Actividad reciente</summary><div class="disclosure-body">
    @include('activity.table', ['activity' => $activity])
    </div></details>
</section>
@endsection
