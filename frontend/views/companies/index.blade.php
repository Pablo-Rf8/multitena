@extends('layout')

@section('title', 'Empresas · Multitena')

@section('content')
<section class="panel">
    <div class="section-head">
        <div>
            <p class="eyebrow">Superadministración</p>
            <h1>Vista general de la plataforma</h1>
            <p class="intro">Empresas, accesos y operación en un solo lugar.</p>
        </div>
        <a class="button" href="{{ route('companies.create') }}">Crear empresa</a>
    </div>
    <div class="stats">
        <div class="stat"><strong>{{ $companyCount }}</strong><span>Empresas</span></div>
        <div class="stat"><strong>{{ $userCount }}</strong><span>Usuarios</span></div>
        <div class="stat"><strong>{{ $branchCount }}</strong><span>Sucursales</span></div>
        <div class="stat"><strong>{{ $pendingCount }}</strong><span>Solicitudes pendientes</span></div>
    </div>
    <form method="get" action="{{ route('dashboard') }}" class="filter-bar">
        <label class="sr-only" for="company-search">Buscar empresa</label>
        <input id="company-search" name="q" type="search" value="{{ request('q') }}" placeholder="Buscar empresa">
        <label class="sr-only" for="company-status">Estado</label>
        <select id="company-status" name="status">
            <option value="">Todos los estados</option>
            <option value="active" @selected(request('status') === 'active')>Activas</option>
            <option value="suspended" @selected(request('status') === 'suspended')>Suspendidas</option>
        </select>
        <button class="button button-secondary" type="submit">Filtrar</button>
    </form>
    @if ($companies->isEmpty())
        <div class="empty-state">Todavía no hay empresas. Creá la primera para empezar.</div>
    @else
        <div class="table-scroll">
            <table>
                <thead><tr><th>Empresa</th><th>Estado</th><th>Sucursales</th><th>Usuarios</th><th>CRM</th><th>Acción</th></tr></thead>
                <tbody>
                    @foreach ($companies as $company)
                        <tr>
                            <td><strong>{{ $company->name }}</strong> @if ($company->is_demo) <span class="tag">DEMO</span> @endif <div class="muted">{{ $company->slug }}</div></td>
                            <td><span @class(['tag', 'tag-muted' => ! $company->is_active])>{{ $company->is_active ? 'Activa' : 'Suspendida' }}</span></td>
                            <td>{{ $company->branches_count }}</td>
                            <td>{{ $company->users_count }}</td>
                            <td>{{ $company->contacts_count }} contactos · {{ $company->opportunities_count }} oportunidades</td>
                            <td><a href="{{ route('companies.show', $company) }}">Ver detalle</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
    {{ $companies->links('pagination.simple') }}
</section>
@endsection
