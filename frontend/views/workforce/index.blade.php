@extends('layout')
@section('title', __('Personal y acceso'))
@section('page-css', 'workforce-index')
@section('content')
<section class="panel">
    <div class="section-head">
        <div>
            <p class="eyebrow">{{ __('Equipo') }}</p>
            <h1>{{ __('Personal y acceso') }}</h1>
            <p class="intro">{{ __('Revisá solicitudes y administrá a las personas de tu empresa.') }}</p>
        </div>
        <a class="button button-primary" href="{{ route('workforce.employees.create') }}"><svg class="icon" aria-hidden="true"><use href="#icon-plus"/></svg>{{ __('Registrar empleado') }}</a>
    </div>
    @include('workforce._nav')
    @if($errors->any())<div class="notice notice-error" role="alert">{{ $errors->first() }}</div>@endif
    <div class="list-heading"><div><strong>{{ __('Empleados') }}</strong><span>{{ $employees->total() }} {{ __('registrados') }}</span></div><a href="{{ route('workforce.categories.index') }}">{{ __('Administrar categorías') }} →</a></div>
    <div class="workforce-list">
        @forelse($employees as $employee)
            <article class="workforce-item">
                <div class="workforce-person">
                    <div><strong>{{ $employee->name }}</strong><p class="muted">{{ $employee->email }}@if($employee->phone) · {{ $employee->phone }}@endif</p></div>
                    <span @class(['tag', 'tag-muted' => $employee->approval_status !== 'pending' && $employee->is_active])>{{ ! $employee->is_active ? __('Suspendido') : (['pending' => __('Pendiente'), 'approved' => __('Aprobado'), 'rejected' => __('Rechazado')][$employee->approval_status] ?? $employee->approval_status) }}</span>
                </div>
                <p class="muted">{{ $employee->category?->name ?? __('Sin categoría') }}</p>
                <div class="actions">
                    <a class="button button-ghost button-sm" href="{{ route('workforce.employees.edit', $employee) }}">{{ __('Editar') }}</a>
                    @if($employee->approval_status === 'pending')
                        <details class="inline-disclosure">
                            <summary>{{ __('Revisar solicitud') }}</summary>
                            <form method="post" action="{{ route('workforce.review', $employee) }}">
                                @csrf @method('PATCH')
                                <label>{{ __('Categoría') }}
                                    <select name="category_id"><option value="">{{ __('Sin categoría') }}</option>@foreach($categories->where('is_active', true) as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select>
                                </label>
                                <div class="actions"><button class="button button-primary button-sm" name="decision" value="approved">{{ __('Aprobar') }}</button><button class="button button-danger button-sm" name="decision" value="rejected" data-confirm="{{ __('¿Rechazar esta solicitud?') }}">{{ __('Rechazar') }}</button></div>
                            </form>
                        </details>
                    @endif
                    <form method="post" action="{{ route('workforce.employees.status', $employee) }}" class="inline-form" data-confirm="{{ $employee->is_active ? __('¿Suspender el acceso de este empleado?') : __('¿Reactivar el acceso de este empleado?') }}">
                        @csrf @method('PATCH')
                        <button name="is_active" value="{{ $employee->is_active ? 0 : 1 }}" class="button button-ghost button-sm {{ $employee->is_active ? 'button-danger' : '' }}">{{ $employee->is_active ? __('Suspender') : __('Reactivar') }}</button>
                    </form>
                </div>
            </article>
        @empty
            <p class="empty-state">{{ __('Todavía no hay empleados. Registrá el primero para comenzar.') }}</p>
        @endforelse
    </div>
    {{ $employees->links() }}
</section>

@endsection
