@extends('layout')

@section('title', __('Mi espacio').' · '.$company->name)

@section('content')
<section class="panel dashboard-hero">
    <p class="eyebrow">{{ __('Mi espacio') }} · {{ $branch->name }}</p>
    <h1>{{ __('Hola, :name', ['name' => auth()->user()->name]) }}</h1>
    <p class="intro">{{ __(':category de :company. Tu información está limitada a la sucursal :branch.', ['category' => auth()->user()->category?->name ?? __('Equipo'), 'company' => $company->name, 'branch' => $branch->name]) }}</p>
    <div class="stats">
        @if(in_array('contacts.read', $permissions, true))<a class="stat stat-link" href="{{ route('contacts.index') }}"><strong>{{ $contactCount }}</strong><span>{{ __('Contactos de mi sucursal') }}</span></a>@endif
        @if(in_array('opportunities.read', $permissions, true))<a class="stat stat-link" href="{{ route('opportunities.index') }}"><strong>{{ $opportunityCount }}</strong><span>{{ __('Oportunidades de mi sucursal') }}</span></a>@endif
    </div>
    @if(empty($permissions))<div class="empty-state">{{ __('Aún no tenés permisos asignados. Solicitá una categoría al administrador de tu empresa.') }}</div>@endif
</section>
@endsection
