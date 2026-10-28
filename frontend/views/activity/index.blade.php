@extends('layout')

@section('title', 'Actividad · Multitena')

@section('content')
<section class="panel">
    <p class="eyebrow">Tu empresa</p>
    <h1>Actividad</h1>
    <p class="intro">Cambios recientes en usuarios, sucursales y CRM.</p>
    @include('activity.table', ['activity' => $activity])
    {{ $activity->links('pagination.simple') }}
</section>
@endsection
