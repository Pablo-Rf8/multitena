@extends('layout')
@section('title', __('Sucursales'))
@section('content')
<section class="panel">
<div class="section-head"><div><p class="eyebrow">{{ $company->name }} / {{ __('Administración') }}</p><h1>{{ __('Sucursales') }}</h1><p class="intro">{{ __('Ubicaciones, responsables y estado de cada sede.') }}</p></div><a class="button button-primary" href="{{ route('branches.create') }}"><svg class="icon" aria-hidden="true"><use href="#icon-plus"/></svg>{{ __('Agregar sucursal') }}</a></div>
<nav class="section-tabs" aria-label="{{ __('Administración de empresa') }}"><a href="{{ route('dashboard') }}">{{ __('Inicio') }}</a><a href="{{ route('branches.index') }}" @if(request()->routeIs('branches.index')) aria-current="page" @endif>{{ __('Sucursales') }}</a><a href="{{ route('team.index') }}" @if(request()->routeIs('team.index')) aria-current="page" @endif>{{ __('Usuarios') }}</a><a href="{{ route('workforce.index') }}">{{ __('Empleados') }}</a></nav>
<div class="list-heading"><div><strong>{{ __('Sedes registradas') }}</strong><span>{{ $company->branches->count() }} {{ __('en total') }}</span></div></div>
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
                                    <div>{{ $assigned->name }} · {{ $assigned->email }} @if(! $assigned->is_active)<span class="tag tag-muted">{{ __('Suspendido') }}</span>@endif @if($assigned->role === 'branch_admin')<a class="button button-ghost button-sm" href="{{ route('branch-users.edit', $assigned) }}">{{ __('Editar usuario') }}</a>@endif</div>
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
</section>
@endsection
