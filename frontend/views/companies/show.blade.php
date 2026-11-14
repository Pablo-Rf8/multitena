@extends('layout')

@section('title', $company->name.' · Multitena')

@section('content')
<section class="panel">
    <a class="back-link" href="{{ route('dashboard') }}">← {{ __('Volver a empresas') }}</a>
    <div class="section-head">
        <div>
            <p class="eyebrow">{{ __('Empresa') }}</p>
            <h1>{{ $company->name }} @if($company->is_demo)<span class="tag">DEMO</span>@endif</h1>
            <p class="intro">{{ $company->slug }} · {{ $company->is_active ? __('Activa') : __('Suspendida') }}</p>
        </div>
        <a class="button button-ghost" href="{{ route('companies.edit', $company) }}">{{ __('Editar empresa') }}</a>
    </div>
    <div class="stats">
        <div class="stat"><strong>{{ $company->branches->count() }}</strong><span>{{ __('Sucursales') }}</span></div>
        <div class="stat"><strong>{{ $userCount }}</strong><span>{{ __('Usuarios') }}</span></div>
        <div class="stat"><strong>{{ $contactCount }}</strong><span>{{ __('Contactos') }}</span></div>
        <div class="stat"><strong>{{ $opportunityCount }}</strong><span>{{ __('Oportunidades') }}</span></div>
    </div>
    <div class="dashboard-shortcuts"><a href="{{ route('companies.employees', $company) }}">{{ __('Ver empleados') }} <span aria-hidden="true">→</span></a><a href="{{ route('company-admins.create', $company) }}">{{ __('Agregar administrador') }} <span aria-hidden="true">→</span></a></div>
    <details class="disclosure"><summary>{{ __('Información de la empresa') }}</summary><div class="disclosure-body">
    <dl class="details">
        <div><dt>{{ __('Código de empresa') }}</dt><dd>{{ $company->code }}</dd></div>
        <div><dt>{{ __('Dominios de acceso') }}</dt><dd>{{ $domains ? implode(', ', $domains) : __('Sin configurar') }}</dd></div>
        <div><dt>{{ __('Moneda') }}</dt><dd>{{ $company->currency }}</dd></div>
        <div><dt>{{ __('Correo de contacto') }}</dt><dd>{{ $company->contact_email ?: __('Sin registrar') }}</dd></div>
        <div><dt>{{ __('Teléfono') }}</dt><dd>{{ $company->phone ?: __('Sin registrar') }}</dd></div>
    </dl>
    <form method="post" action="{{ route('companies.status', $company) }}" class="actions" data-confirm="{{ $company->is_active ? __('¿Suspender la empresa y bloquear el acceso de todos sus usuarios?') : __('¿Activar la empresa?') }}">
        @csrf @method('PATCH')
        <input type="hidden" name="is_active" value="{{ $company->is_active ? '0' : '1' }}">
        <button type="submit" class="button {{ $company->is_active ? 'button-danger' : '' }}">{{ $company->is_active ? __('Suspender empresa') : __('Activar empresa') }}</button>
    </form>
    </div></details>

    <div class="module-links">
    <a href="{{ route('companies.branches', $company) }}"><strong>{{ __('Sucursales') }}</strong><span>{{ $company->branches->count() }} {{ __('registradas') }}</span></a>
    <a href="{{ route('companies.admins', $company) }}"><strong>{{ __('Administradores') }}</strong><span>{{ $company->users->count() }} {{ __('registrados') }}</span></a>
    <a href="{{ route('companies.employees', $company) }}"><strong>{{ __('Empleados') }}</strong><span>{{ $employeeCount }} {{ __('registrados') }}</span></a>
    <a href="{{ route('companies.activity', $company) }}"><strong>{{ __('Actividad') }}</strong><span>{{ __('Cambios recientes de esta empresa') }}</span></a>
</div>
</section>
@endsection
