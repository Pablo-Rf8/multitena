@extends('layout')
@section('title', $company->name.' · Multitena')
@section('page-css', 'companies-dashboard')
@section('content')
<div class="minimal-home compact-dashboard">
    <header class="minimal-page-head">
        <div><p class="eyebrow">{{ $company->name }} <span class="head-divider">/</span> {{ $branch->name }}</p><h1>{{ __('Inicio') }}</h1></div>
        <div class="head-actions"><a class="button button-ghost" href="{{ route('opportunities.create') }}">{{ __('Nueva oportunidad') }}</a><a class="button button-primary" href="{{ route('contacts.create') }}"><svg class="icon" aria-hidden="true"><use href="#icon-plus"/></svg>{{ __('Nuevo contacto') }}</a></div>
    </header>
    <section class="quiet-metrics" aria-label="{{ __('Resumen') }}">
        <a href="{{ route('contacts.index') }}"><strong>{{ $contactCount }}</strong><span>{{ $contactCount === 1 ? __('Contacto') : __('Contactos') }}</span></a>
        <a href="{{ route('opportunities.index') }}"><strong>{{ $opportunityCount }}</strong><span>{{ $opportunityCount === 1 ? __('Oportunidad') : __('Oportunidades') }}</span></a>
        <div><strong>{{ number_format($wonAmount / 100, 2) }}</strong><span>{{ __('Ganado') }} · {{ $company->currency }}</span></div>
        @if($isCompanyAdmin)<a href="{{ route('branches.index') }}"><strong>{{ $company->branches->count() }}</strong><span>{{ $company->branches->count() === 1 ? __('Sucursal') : __('Sucursales') }}</span></a>@endif
    </section>
    <div class="dashboard-columns">
        <div class="dashboard-links-column">
            <section class="work-section" aria-labelledby="work-title">
                <div class="list-heading"><h2 id="work-title">{{ __('Áreas de trabajo') }}</h2></div>
                <div class="minimal-link-list">
                    <a href="{{ route('contacts.index') }}"><svg class="icon" aria-hidden="true"><use href="#icon-users"/></svg><strong>{{ __('Contactos') }}</strong><span>{{ __('Personas y seguimiento') }}</span><b aria-hidden="true">→</b></a>
                    <a href="{{ route('opportunities.index') }}"><svg class="icon" aria-hidden="true"><use href="#icon-briefcase"/></svg><strong>{{ __('Oportunidades') }}</strong><span>{{ __('Negocios y etapas') }}</span><b aria-hidden="true">→</b></a>
                    <a href="{{ route('credentials.index') }}"><svg class="icon" aria-hidden="true"><use href="#icon-key"/></svg><strong>{{ __('Credenciales') }}</strong><span>{{ __('Accesos propios y compartidos') }}</span><b aria-hidden="true">→</b></a>
                </div>
            </section>
            @if($isCompanyAdmin)
            <section class="work-section" aria-labelledby="company-title">
                <div class="list-heading"><h2 id="company-title">{{ __('Administración') }}</h2></div>
                <div class="minimal-link-list">
                    <a href="{{ route('team.index') }}"><svg class="icon" aria-hidden="true"><use href="#icon-users"/></svg><strong>{{ __('Usuarios') }}</strong><span>{{ __('Administradores de la empresa') }}</span><b aria-hidden="true">→</b></a>
                    <a href="{{ route('workforce.index') }}"><svg class="icon" aria-hidden="true"><use href="#icon-shield"/></svg><strong>{{ __('Empleados') }}</strong><span>{{ __('Solicitudes y acceso') }}</span><b aria-hidden="true">→</b></a>
                    <a href="{{ route('branches.index') }}"><svg class="icon" aria-hidden="true"><use href="#icon-home"/></svg><strong>{{ __('Sucursales') }}</strong><span>{{ __('Sedes y responsables') }}</span><b aria-hidden="true">→</b></a>
                    <a href="{{ route('workforce.settings.edit') }}"><svg class="icon" aria-hidden="true"><use href="#icon-settings"/></svg><strong>{{ __('Acceso') }}</strong><span>{{ __('Código de empresa:') }} {{ $company->code }}</span><b aria-hidden="true">→</b></a>
                </div>
            </section>
            @endif
        </div>
        @if($isCompanyAdmin)
        <section class="work-section dashboard-activity" aria-labelledby="recent-activity-title">
            <div class="list-heading"><h2 id="recent-activity-title">{{ __('Actividad reciente') }}</h2><a href="{{ route('activity.index') }}">{{ __('Ver toda la actividad') }} →</a></div>
            @include('activity.table', ['activity' => $recentActivity])
        </section>
        @endif
    </div>
</div>
@endsection
