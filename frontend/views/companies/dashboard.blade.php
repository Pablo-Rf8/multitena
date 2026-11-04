@extends('layout')

@section('title', $company->name.' · Multitena')

@section('content')
<section class="panel">
    <p class="eyebrow">{{ $isCompanyAdmin ? __('Empresa') : __('Sucursal') }} · {{ $branch->name }}</p>
    <h1>{{ $company->name }} @if ($company->is_demo) <span class="tag">DEMO</span> @endif</h1>
    <p class="intro">{{ __('Resumen de tu trabajo.') }}</p>
    <div class="stats">
        <a class="stat stat-link" href="{{ route('contacts.index') }}"><strong>{{ $contactCount }}</strong><span>{{ __('Contactos') }}</span></a>
        <a class="stat stat-link" href="{{ route('opportunities.index') }}"><strong>{{ $opportunityCount }}</strong><span>{{ __('Oportunidades') }}</span></a>
        <div class="stat"><strong>{{ number_format($wonAmount / 100, 2) }}</strong><span>Monto ganado · {{ $company->currency }}</span></div>
        @if ($isCompanyAdmin)
            <div class="stat"><strong>{{ $company->branches->count() }}</strong><span>{{ __('Sucursales') }}</span></div>
        @endif
    </div>
    <div class="actions">
        <a class="button button-primary" href="{{ route('contacts.create') }}"><svg class="icon" aria-hidden="true"><use href="#icon-plus"/></svg>{{ __('Agregar contacto') }}</a>
    </div>
    @if ($isCompanyAdmin)
        <details class="disclosure"><summary>{{ __('Datos de acceso') }}</summary><div class="disclosure-body"><p>{{ __('Código para iniciar sesión:') }} <strong>{{ $company->code }}</strong></p></div></details>
        <details class="disclosure" id="branches"><summary>{{ __('Sucursales') }} <small>{{ $company->branches->count() }} {{ __('registradas') }}</small></summary><div class="disclosure-body">
        <div class="actions">
            <a class="button button-ghost" href="{{ route('branches.create') }}">{{ __('Agregar sucursal') }}</a>
        </div>
        <div class="table-scroll">
            <table>
                <thead><tr><th>{{ __('Sucursal') }}</th><th>{{ __('Estado') }}</th><th>{{ __('Administradores asignados') }}</th><th>{{ __('Acción') }}</th></tr></thead>
                <tbody>
                    @foreach ($company->branches as $item)
                        <tr>
                            <td>{{ $item->name }} @if ($item->is_central) <span class="tag">{{ __('Central') }}</span> @endif <div class="muted">{{ $item->address }}</div></td>
                            <td><span @class(['tag', 'tag-muted' => ! $item->is_active])>{{ $item->is_active ? __('Activa') : __('Suspendida') }}</span></td>
                            <td>
                                @forelse ($company->users->where('branch_id', $item->id) as $assigned)
                                    <div>{{ $assigned->name }} · {{ $assigned->email }} @if(! $assigned->is_active)<span class="tag tag-muted">{{ __('Suspendido') }}</span>@endif @if($assigned->role === 'branch_admin')<a class="button button-ghost button-sm" href="{{ route('branch-users.edit', $assigned) }}">{{ __('Editar') }}</a>@endif</div>
                                @empty
                                    <span class="hint">{{ __('Sin administrador asignado') }}</span>
                                @endforelse
                            </td>
                            <td>
                                <a class="button button-ghost button-sm" href="{{ route('branches.edit', $item) }}">{{ __('Editar') }}</a>
                                @unless ($item->is_central)
                                    <form method="post" action="{{ route('branches.status', $item) }}" class="inline-form" data-confirm="{{ $item->is_active ? __('¿Suspender esta sucursal y bloquear el acceso de sus usuarios?') : __('¿Activar esta sucursal?') }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="is_active" value="{{ $item->is_active ? '0' : '1' }}">
                                        <button type="submit" class="button button-ghost button-sm {{ $item->is_active ? 'button-danger' : '' }}">{{ $item->is_active ? __('Suspender') : __('Activar') }}</button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        </div></details>
        <details class="disclosure" id="users"><summary>{{ __('Usuarios de la empresa') }} <small>{{ $company->users->count() }} {{ __('registrados') }}</small></summary><div class="disclosure-body">
        <div class="actions"><a class="button button-ghost" href="{{ route('branch-users.create') }}">{{ __('Agregar administrador de sucursal') }}</a></div>
        <div class="table-scroll">
            <table>
                <thead><tr><th>{{ __('Nombre') }}</th><th>{{ __('Correo') }}</th><th>{{ __('Rol') }}</th><th>{{ __('Sucursal') }}</th><th>{{ __('Estado') }}</th><th>{{ __('Acción') }}</th></tr></thead>
                <tbody>
                    @foreach ($company->users as $member)
                        <tr>
                            <td>{{ $member->name }}</td><td>{{ $member->email }}</td>
                            <td>{{ ['company_admin' => __('Administrador de empresa'), 'branch_admin' => __('Administrador de sucursal'), 'employee' => __('Empleado')][$member->role] ?? $member->role }}</td>
                            <td>{{ $company->branches->firstWhere('id', $member->branch_id)?->name }}</td>
                            <td>{{ $member->is_active ? __('Activo') : __('Suspendido') }}</td>
                            <td>
                                @if ($member->role === 'branch_admin')
                                    <a class="button button-ghost button-sm" href="{{ route('branch-users.edit', $member) }}">{{ __('Editar') }}</a>
                                    <form method="post" action="{{ route('branch-users.status', $member) }}" class="inline-form" data-confirm="{{ $member->is_active ? __('¿Suspender este usuario?') : __('¿Activar este usuario?') }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="is_active" value="{{ $member->is_active ? '0' : '1' }}">
                                        <button class="button button-ghost button-sm {{ $member->is_active ? 'button-danger' : '' }}" type="submit">{{ $member->is_active ? __('Suspender') : __('Activar') }}</button>
                                    </form>
                                @else
                                    <span class="muted">{{ $member->role === 'employee' ? 'Ver en Personal y acceso' : 'Gestiona el superadministrador' }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        </div></details>
    @else
        <h2>{{ __('Sucursal') }}</h2>
        <p>{{ $branch->name }}</p>
    @endif
</section>
@endsection
