@extends('layout')
@section('title', __('Administradores').' · '. $company->name)
@section('content')
<section class="panel">
<a class="back-link" href="{{ route('companies.show', $company) }}">← {{ __('Volver a empresa') }}</a>
<p class="eyebrow">{{ $company->name }}</p>
<h1>{{ __('Administradores') }}</h1>
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
</section>
@endsection
