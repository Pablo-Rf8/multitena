@extends('layout')

@section('title', 'Mi espacio · '.$company->name)

@section('content')
<section class="panel dashboard-hero">
    <p class="eyebrow">Mi espacio · {{ $branch->name }}</p>
    <h1>Hola, {{ auth()->user()->name }}</h1>
    <p class="intro">{{ auth()->user()->category?->name ?? 'Equipo' }} de {{ $company->name }}. Tu información está limitada a la sucursal {{ $branch->name }}.</p>
    <div class="stats">
        @if(in_array('contacts.read', $permissions, true))<a class="stat stat-link" href="{{ route('contacts.index') }}"><strong>{{ $contactCount }}</strong><span>Contactos de mi sucursal</span></a>@endif
        @if(in_array('opportunities.read', $permissions, true))<a class="stat stat-link" href="{{ route('opportunities.index') }}"><strong>{{ $opportunityCount }}</strong><span>Oportunidades de mi sucursal</span></a>@endif
    </div>
    @if(empty($permissions))<div class="empty-state">Aún no tenés permisos asignados. Solicitá una categoría al administrador de tu empresa.</div>@endif
</section>
@endsection
