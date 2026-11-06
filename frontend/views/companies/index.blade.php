@extends('layout')

@section('title', __('Empresas').' · Multitena')

@section('content')
<section class="panel">
    <div class="section-head">
        <div>
            <p class="eyebrow">{{ __('Superadministración') }}</p>
            <h1>{{ __('Vista general de la plataforma') }}</h1>
            <p class="intro">{{ __('Empresas, accesos y operación en un solo lugar.') }}</p>
        </div>
        <a class="button button-primary" href="{{ route('companies.create') }}"><svg class="icon" aria-hidden="true"><use href="#icon-plus"/></svg>{{ __('Crear empresa') }}</a>
    </div>
    <div class="stats">
        <div class="stat"><strong>{{ $companyCount }}</strong><span>{{ __('Empresas') }}</span></div>
        <div class="stat"><strong>{{ $userCount }}</strong><span>{{ __('Usuarios') }}</span></div>
        <div class="stat"><strong>{{ $branchCount }}</strong><span>{{ __('Sucursales') }}</span></div>
        <div class="stat"><strong>{{ $pendingCount }}</strong><span>{{ __('Solicitudes pendientes') }}</span></div>
    </div>
    <p class="muted">{{ __('Gestión de Credenciales') }}: {{ $credentialTemplateCount }} {{ __('plantillas') }} · {{ $credentialCount }} {{ __('registros') }}. {{ __('Solo estadísticas; los datos y secretos pertenecen a cada empresa.') }}</p>
    <form method="get" action="{{ route('dashboard') }}" class="filter-bar">
        <label class="sr-only" for="company-search">{{ __('Buscar empresa') }}</label>
        <input id="company-search" name="q" type="search" value="{{ request('q') }}" placeholder="{{ __('Buscar empresa') }}">
        <label class="sr-only" for="company-status">{{ __('Estado') }}</label>
        <select id="company-status" name="status">
            <option value="">{{ __('Todos los estados') }}</option>
            <option value="active" @selected(request('status') === 'active')>{{ __('Activas') }}</option>
            <option value="suspended" @selected(request('status') === 'suspended')>{{ __('Suspendidas') }}</option>
        </select>
        <button class="button button-ghost" type="submit">{{ __('Filtrar') }}</button>
    </form>
    @if ($companies->isEmpty())
        <div class="empty-state">{{ __('Todavía no hay empresas. Creá la primera para empezar.') }}</div>
    @else
        <div class="table-scroll">
            <table>
                <thead><tr><th>{{ __('Empresa') }}</th><th>{{ __('Estado') }}</th><th>{{ __('Sucursales') }}</th><th>{{ __('Usuarios') }}</th><th>CRM</th><th>{{ __('Acción') }}</th></tr></thead>
                <tbody>
                    @foreach ($companies as $company)
                        <tr>
                            <td><strong>{{ $company->name }}</strong> @if ($company->is_demo) <span class="tag">DEMO</span> @endif <div class="muted">{{ $company->slug }}</div></td>
                            <td><span @class(['tag', 'tag-muted' => ! $company->is_active])>{{ $company->is_active ? __('Activa') : __('Suspendida') }}</span></td>
                            <td>{{ $company->branches_count }}</td>
                            <td>{{ $company->users_count }}</td>
                            <td>{{ $company->contacts_count }} {{ __('contactos') }} · {{ $company->opportunities_count }} {{ __('oportunidades') }}</td>
                            <td><a class="button button-ghost button-sm" href="{{ route('companies.show', $company) }}">{{ __('Ver detalle') }}</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
    {{ $companies->links('pagination.simple') }}
</section>
@endsection
