@extends('layout')

@section('title', 'Empleados · '.$company->name)

@section('content')
<section class="panel">
    <a class="back-link" href="{{ route('companies.show', $company) }}">← Volver a {{ $company->name }}</a>
    <div class="section-head">
        <div><p class="eyebrow">Empresa</p><h1>Empleados de {{ $company->name }}</h1><p class="intro">Consultá el personal y su estado de aprobación por empresa.</p></div>
        <a class="button button-primary" href="{{ route('employees.create', ['company_id' => $company->id]) }}">Registrar empleado</a>
    </div>
    <div class="table-scroll"><table>
        <thead><tr><th>Nombre</th><th>Correo</th><th>Teléfono</th><th>Sucursal</th><th>Categoría</th><th>Acceso</th></tr></thead>
        <tbody>
            @forelse($employees as $employee)
                <tr><td>{{ $employee->name }}</td><td>{{ $employee->email }}</td><td>{{ $employee->phone ?: '—' }}</td><td>{{ $employee->branch?->name ?? '—' }}</td><td>{{ $employee->category?->name ?? 'Sin asignar' }}</td><td>{{ $employee->is_active ? ucfirst($employee->approval_status) : 'Suspendido' }}</td></tr>
            @empty
                <tr><td colspan="6">Todavía no hay empleados en esta empresa.</td></tr>
            @endforelse
        </tbody>
    </table></div>
    {{ $employees->links() }}
</section>
@endsection
