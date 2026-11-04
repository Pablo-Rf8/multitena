@extends('layout')

@section('title', __('Empleados').' · '.$company->name)

@section('content')
<section class="panel">
    <a class="back-link" href="{{ route('companies.show', $company) }}">← {{ __('Volver a') }} {{ $company->name }}</a>
    <div class="section-head">
        <div><p class="eyebrow">{{ __('Empresa') }}</p><h1>Empleados de {{ $company->name }}</h1><p class="intro">{{ __('Consultá el personal y su estado de aprobación por empresa.') }}</p></div>
        <a class="button button-primary" href="{{ route('employees.create', ['company_id' => $company->id]) }}"><svg class="icon" aria-hidden="true"><use href="#icon-plus"/></svg>{{ __('Registrar empleado') }}</a>
    </div>
    <div class="table-scroll"><table>
        <thead><tr><th>{{ __('Nombre') }}</th><th>{{ __('Correo') }}</th><th>{{ __('Teléfono') }}</th><th>{{ __('Sucursal') }}</th><th>{{ __('Categoría') }}</th><th>{{ __('Acceso') }}</th></tr></thead>
        <tbody>
            @forelse($employees as $employee)
                <tr><td>{{ $employee->name }}</td><td>{{ $employee->email }}</td><td>{{ $employee->phone ?: '—' }}</td><td>{{ $employee->branch?->name ?? '—' }}</td><td>{{ $employee->category?->name ?? 'Sin asignar' }}</td><td>{{ $employee->is_active ? ucfirst($employee->approval_status) : __('Suspendido') }}</td></tr>
            @empty
                <tr><td colspan="6">{{ __('Todavía no hay empleados en esta empresa.') }}</td></tr>
            @endforelse
        </tbody>
    </table></div>
    {{ $employees->links() }}
</section>
@endsection
