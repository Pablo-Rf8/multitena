@extends('layout')

@section('title', $company->name.' · Multitena')

@section('content')
<section class="panel">
    <p class="eyebrow">{{ $isCompanyAdmin ? 'Administración de empresa' : 'Administración de sucursal' }}</p>
    <h1>{{ $company->name }} @if ($company->is_demo) <span class="tag">DEMO</span> @endif</h1>
    <p class="intro">Tu sucursal asignada es {{ $branch->name }}.</p>
    @if ($isCompanyAdmin)
        <div class="actions">
            <a class="button" href="{{ route('branches.create') }}">Agregar sucursal</a>
            <a class="button button-secondary" href="{{ route('branch-users.create') }}">Agregar administrador</a>
        </div>
        <h2>Sucursales</h2>
        <div class="table-scroll">
            <table>
                <thead><tr><th>Sucursal</th><th>Administradores asignados</th></tr></thead>
                <tbody>
                    @foreach ($company->branches as $item)
                        <tr>
                            <td>{{ $item->name }} @if ($item->is_central) <span class="tag">Central</span> @endif</td>
                            <td>
                                @forelse ($company->users->where('branch_id', $item->id) as $assigned)
                                    <div>{{ $assigned->name }} · {{ $assigned->email }}</div>
                                @empty
                                    <span class="hint">Sin administrador asignado</span>
                                @endforelse
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
