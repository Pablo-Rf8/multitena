@extends('layout')

@section('title', 'Empresas · Multitena')

@section('content')
<section class="panel">
    <div class="section-head">
        <div>
            <p class="eyebrow">Superadministración</p>
            <h1>Empresas</h1>
            <p class="intro">Cada empresa inicia con una sucursal Central y un administrador asignado.</p>
        </div>
        <a class="button" href="{{ route('companies.create') }}">Crear empresa</a>
    </div>
    @if ($companies->isEmpty())
        <div class="empty-state">Todavía no hay empresas. Creá la primera para empezar.</div>
    @else
        <div class="table-scroll">
            <table>
                <thead><tr><th>Empresa</th><th>Sucursal inicial</th><th>Administrador</th></tr></thead>
                <tbody>
                    @foreach ($companies as $company)
                        <tr>
                            <td><strong>{{ $company->name }}</strong> @if ($company->is_demo) <span class="tag">DEMO</span> @endif</td>
                            <td>{{ $company->branches->firstWhere('is_central', true)?->name ?? '—' }}</td>
                            <td>{{ $company->users->firstWhere('role', 'company_admin')?->email ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
@endsection
