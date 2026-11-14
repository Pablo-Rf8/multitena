@extends('layout')

@section('title', __('Empresas').' · Multitena')
@section('page-css', 'companies-index')

@section('content')
<div class="minimal-home platform-home">
    <header class="minimal-page-head"><div><p class="eyebrow">{{ __('Administración global') }}</p><h1>{{ __('Empresas') }}</h1><p class="intro">{{ __('Organizaciones y accesos de la plataforma') }}</p></div><div class="head-actions"><a class="button button-ghost" href="{{ route('employees.create') }}">{{ __('Registrar empleado') }}</a><a class="button button-primary" href="{{ route('companies.create') }}"><svg class="icon" aria-hidden="true"><use href="#icon-plus"/></svg>{{ __('Crear empresa') }}</a></div></header>
    <section class="quiet-metrics" aria-label="{{ __('Resumen de plataforma') }}">
        <div><strong>{{ $companyCount }}</strong><span>{{ __('Empresas') }} · {{ $activeCount }} {{ __('activas') }}</span></div>
        <div><strong>{{ $userCount }}</strong><span>{{ __('Usuarios') }}</span></div>
        <div><strong>{{ $branchCount }}</strong><span>{{ __('Sucursales') }}</span></div>
        <div><strong>{{ $pendingCount }}</strong><span>{{ __('Solicitudes pendientes') }}</span></div>
    </section>
    <section class="work-section" aria-labelledby="companies-title">
    <div class="list-heading"><div><h2 id="companies-title">{{ __('Directorio de empresas') }}</h2><span>{{ $companyCount }} {{ __('registradas') }}</span></div><a href="{{ route('credentials.index') }}">{{ __('Compartidas conmigo') }} →</a></div>
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
    @if($pendingCount > 0)
    <div class="platform-pending" role="status"><strong>{{ __('Solicitudes pendientes') }}</strong><span>{{ $pendingCount }} {{ __('empleados esperan aprobación en sus empresas.') }}</span><a class="button button-ghost button-sm" href="#companies-title">{{ __('Revisar empresas') }}</a></div>
    @endif
    <p class="minimal-footnote">{{ __('Gestión de Credenciales') }} · {{ $credentialCount }} {{ $credentialCount === 1 ? __('registro') : __('registros') }} · {{ $credentialTemplateCount }} {{ $credentialTemplateCount === 1 ? __('plantilla') : __('plantillas') }}. {{ __('Solo estadísticas generales; los secretos pertenecen a cada empresa.') }}</p>
</div>
@endsection
