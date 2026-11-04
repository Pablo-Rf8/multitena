@extends('layout')

@section('title', __('Actividad').' · Multitena')

@section('content')
<section class="panel">
    <p class="eyebrow">{{ __('Tu empresa') }}</p>
    <h1>{{ __('Actividad') }}</h1>
    <p class="intro">{{ __('Cambios recientes en usuarios, sucursales, CRM y credenciales.') }}</p>
    @include('activity.table', ['activity' => $activity])
    {{ $activity->links('pagination.simple') }}
</section>
@endsection
