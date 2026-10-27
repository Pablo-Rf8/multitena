@extends('layout')

@section('title', $company->name.' · Multitena')

@section('content')
<section class="panel">
    <p class="eyebrow">Panel de empresa</p>
    <h1>{{ $company->name }}</h1>
    <p class="intro">Ingresaste como administrador de {{ $branch->name }}.</p>
    <h2>Sucursales</h2>
    <ul class="branch-list">
        @foreach ($company->branches as $item)
            <li>{{ $item->name }} @if ($item->is_central) <span class="tag">Principal</span> @endif</li>
        @endforeach
    </ul>
</section>
@endsection
