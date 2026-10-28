@extends('layout')

@section('title', $company->name.' · Multitena')

@section('content')
<section class="panel">
    <p class="eyebrow">{{ $isCompanyAdmin ? 'Administración de empresa' : 'Administración de sucursal' }}</p>
    <h1>{{ $company->name }} @if ($company->is_demo) <span class="tag">DEMO</span> @endif</h1>
    <p class="intro">Tu sucursal asignada es {{ $branch->name }}.</p>
    <div class="stats">
        <a class="stat stat-link" href="{{ route('contacts.index') }}"><strong>{{ $contactCount }}</strong><span>Contactos</span></a>
        <a class="stat stat-link" href="{{ route('opportunities.index') }}"><strong>{{ $opportunityCount }}</strong><span>Oportunidades</span></a>
        <div class="stat"><strong>{{ number_format($wonAmount / 100, 2) }}</strong><span>Monto ganado · {{ $company->currency }}</span></div>
        @if ($isCompanyAdmin)
            <div class="stat"><strong>{{ $company->branches->count() }}</strong><span>Sucursales</span></div>
        @endif
    </div>
    <div class="actions">
        <a class="button" href="{{ route('contacts.create') }}">Agregar contacto</a>
        <a class="button button-secondary" href="{{ route('opportunities.index') }}">Ver oportunidades</a>
    </div>
    @if ($isCompanyAdmin)
        <div class="actions">
            <a class="button button-secondary" href="{{ route('branches.create') }}">Agregar sucursal</a>
            <a class="button button-secondary" href="{{ route('branch-users.create') }}">Agregar administrador</a>
        </div>
        <h2>Sucursales</h2>
        <div class="table-scroll">
            <table>
                <thead><tr><th>Sucursal</th><th>Estado</th><th>Administradores asignados</th><th>Acción</th></tr></thead>
                <tbody>
                    @foreach ($company->branches as $item)
                        <tr>
                            <td>{{ $item->name }} @if ($item->is_central) <span class="tag">Central</span> @endif <div class="muted">{{ $item->address }}</div></td>
                            <td><span @class(['tag', 'tag-muted' => ! $item->is_active])>{{ $item->is_active ? 'Activa' : 'Suspendida' }}</span></td>
                            <td>
                                @forelse ($company->users->where('branch_id', $item->id) as $assigned)
                                    <div>{{ $assigned->name }} · {{ $assigned->email }} @if(! $assigned->is_active)<span class="tag tag-muted">Suspendido</span>@endif @if($assigned->role === 'branch_admin')<a href="{{ route('branch-users.edit', $assigned) }}">Editar</a>@endif</div>
                                @empty
                                    <span class="hint">Sin administrador asignado</span>
                                @endforelse
                            </td>
                            <td>
                                <a href="{{ route('branches.edit', $item) }}">Editar</a>
                                @unless ($item->is_central)
                                    <form method="post" action="{{ route('branches.status', $item) }}" class="inline-form" data-confirm="{{ $item->is_active ? '¿Suspender esta sucursal y bloquear el acceso de sus usuarios?' : '¿Activar esta sucursal?' }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="is_active" value="{{ $item->is_active ? '0' : '1' }}">
                                        <button type="submit" class="text-button">{{ $item->is_active ? 'Suspender' : 'Activar' }}</button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <h2>Usuarios de la empresa</h2>
        <div class="table-scroll">
            <table>
                <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Sucursal</th><th>Estado</th><th>Acción</th></tr></thead>
                <tbody>
                    @foreach ($company->users as $member)
                        <tr>
                            <td>{{ $member->name }}</td><td>{{ $member->email }}</td>
                            <td>{{ $member->role === 'company_admin' ? 'Administrador de empresa' : 'Administrador de sucursal' }}</td>
                            <td>{{ $company->branches->firstWhere('id', $member->branch_id)?->name }}</td>
                            <td>{{ $member->is_active ? 'Activo' : 'Suspendido' }}</td>
                            <td>
                                @if ($member->role === 'branch_admin')
                                    <a href="{{ route('branch-users.edit', $member) }}">Editar</a>
                                    <form method="post" action="{{ route('branch-users.status', $member) }}" class="inline-form" data-confirm="{{ $member->is_active ? '¿Suspender este usuario?' : '¿Activar este usuario?' }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="is_active" value="{{ $member->is_active ? '0' : '1' }}">
                                        <button class="text-button" type="submit">{{ $member->is_active ? 'Suspender' : 'Activar' }}</button>
                                    </form>
                                @else
                                    <span class="muted">Gestiona el superadministrador</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <h2>Sucursal</h2>
        <p>{{ $branch->name }}</p>
    @endif
</section>
@endsection
