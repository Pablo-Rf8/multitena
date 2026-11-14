@extends('layout')
@section('title', __('Sucursales').' · '. $company->name)
@section('content')
<section class="panel">
<a class="back-link" href="{{ route('companies.show', $company) }}">← {{ __('Volver a empresa') }}</a>
<p class="eyebrow">{{ $company->name }}</p>
<h1>{{ __('Sucursales') }}</h1>
<div class="table-scroll">
        <table>
            <thead><tr><th>{{ __('Nombre') }}</th><th>{{ __('Estado') }}</th><th>{{ __('Dirección') }}</th></tr></thead>
            <tbody>
                @foreach($company->branches as $branch)
                    <tr><td>{{ $branch->name }} @if($branch->is_central)<span class="tag">{{ __('Central') }}</span>@endif</td><td>{{ $branch->is_active ? __('Activa') : __('Suspendida') }}</td><td>{{ $branch->address ?: '—' }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
@endsection
