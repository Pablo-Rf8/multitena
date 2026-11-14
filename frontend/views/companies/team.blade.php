@extends('layout')
@section('title', __('Usuarios de la empresa'))
@section('content')
<section class="panel">
<div class="section-head"><div><p class="eyebrow">{{ $company->name }} / {{ __('Administración') }}</p><h1>{{ __('Usuarios de la empresa') }}</h1><p class="intro">{{ __('Administradores con acceso a esta empresa y sus sucursales.') }}</p></div><a class="button button-primary" href="{{ route('branch-users.create') }}"><svg class="icon" aria-hidden="true"><use href="#icon-plus"/></svg>{{ __('Agregar administrador') }}</a></div>
<nav class="section-tabs" aria-label="{{ __('Administración de empresa') }}"><a href="{{ route('dashboard') }}">{{ __('Inicio') }}</a><a href="{{ route('branches.index') }}" @if(request()->routeIs('branches.index')) aria-current="page" @endif>{{ __('Sucursales') }}</a><a href="{{ route('team.index') }}" @if(request()->routeIs('team.index')) aria-current="page" @endif>{{ __('Usuarios') }}</a><a href="{{ route('workforce.index') }}">{{ __('Empleados') }}</a></nav>
<div class="list-heading"><div><strong>{{ __('Administradores') }}</strong><span>{{ $company->users->count() }} {{ __('registrados') }}</span></div><a href="{{ route('workforce.index') }}">{{ __('Ver empleados') }} →</a></div>
        <div class="table-scroll">
            <table>
                <thead><tr><th>{{ __('Nombre') }}</th><th>{{ __('Correo') }}</th><th>{{ __('Rol') }}</th><th>{{ __('Sucursal') }}</th><th>{{ __('Estado') }}</th><th>{{ __('Acción') }}</th></tr></thead>
                <tbody>
                    @foreach ($company->users as $member)
                        <tr>
                            <td><strong>{{ $member->name }}</strong></td><td>{{ $member->email }}</td>
                            <td>{{ ['company_admin' => __('Administrador de empresa'), 'branch_admin' => __('Administrador de sucursal')][$member->role] ?? $member->role }}</td>
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
                                    <span class="muted">{{ __('Gestiona el superadministrador') }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
</section>
@endsection
