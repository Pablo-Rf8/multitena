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

    <details class="disclosure"><summary>{{ __('Sucursales') }} <small>{{ $company->branches->count() }} {{ __('registradas') }}</small></summary><div class="disclosure-body">
    <div class="table-scroll">
        <table>
            <thead><tr><th>{{ __('Nombre') }}</th><th>{{ __('Estado') }}</th><th>{{ __('Dirección') }}</th></tr></thead>
            <tbody>
                @foreach($company->branches as $branch)
                    <tr><td>{{ $branch->name }} @if($branch->is_central)<span class="tag">{{ __('Central') }}</span>@endif</td><td>{{ $branch->is_active ? __('Activa') : __('Suspendida') }}</td><td>{{ $branch->address ?: '—' }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
    </div></details>
    <details class="disclosure"><summary>{{ __('Administradores') }} <small>{{ $company->users->whereIn('role', ['company_admin', 'branch_admin'])->count() }} {{ __('registrados') }}</small></summary><div class="disclosure-body">
    <div class="section-head section-subhead">
        <h2>{{ __('Administradores de empresa') }}</h2>
        <a class="button button-ghost" href="{{ route('company-admins.create', $company) }}">{{ __('Agregar administrador') }}</a>
    </div>
    <div class="table-scroll">
        <table>
            <thead><tr><th>{{ __('Nombre') }}</th><th>{{ __('Correo') }}</th><th>{{ __('Estado') }}</th><th>{{ __('Último ingreso') }}</th><th>{{ __('Acciones') }}</th></tr></thead>
            <tbody>
                @foreach($company->users->where('role', 'company_admin') as $member)
                    <tr>
                        <td>{{ $member->name }}</td><td>{{ $member->email }}</td>
                        <td>{{ $member->is_active ? __('Activo') : __('Suspendido') }}</td>
                        <td>{{ $member->last_login_at?->format('d/m/Y H:i') ?? __('Nunca') }}</td>
                        <td>
                            <a class="button button-ghost button-sm" href="{{ route('company-admins.edit', [$company, $member]) }}">{{ __('Editar') }}</a>
                            <form method="post" action="{{ route('company-admins.status', [$company, $member]) }}" class="inline-form" data-confirm="{{ $member->is_active ? __('¿Suspender este administrador?') : __('¿Activar este administrador?') }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="is_active" value="{{ $member->is_active ? '0' : '1' }}">
                                <button type="submit" class="button button-ghost button-sm {{ $member->is_active ? 'button-danger' : '' }}">{{ $member->is_active ? __('Suspender') : __('Activar') }}</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <h2>{{ __('Administradores de sucursal') }}</h2>
    <div class="table-scroll"><table><thead><tr><th>{{ __('Nombre') }}</th><th>{{ __('Correo') }}</th><th>{{ __('Sucursal') }}</th><th>{{ __('Estado') }}</th></tr></thead><tbody>
        @forelse($company->users->where('role', 'branch_admin') as $member)
            <tr><td>{{ $member->name }}</td><td>{{ $member->email }}</td><td>{{ $company->branches->firstWhere('id', $member->branch_id)?->name ?? '—' }}</td><td>{{ $member->is_active ? __('Activo') : __('Suspendido') }}</td></tr>
        @empty<tr><td colspan="4">{{ __('Sin administradores de sucursal.') }}</td></tr>@endforelse
    </tbody></table></div>
    </div></details>
    <details class="disclosure"><summary>{{ __('Actividad reciente') }}</summary><div class="disclosure-body">
    @include('activity.table', ['activity' => $activity])
    </div></details>
</section>
@endsection
