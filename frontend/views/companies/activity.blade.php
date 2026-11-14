@extends('layout')
@section('title', __('Actividad reciente').' · '. $company->name)
@section('content')
<section class="panel">
<a class="back-link" href="{{ route('companies.show', $company) }}">← {{ __('Volver a empresa') }}</a>
<p class="eyebrow">{{ $company->name }}</p>
<h1>{{ __('Actividad reciente') }}</h1>
@include('activity.table', ['activity' => $activity])
</section>
@endsection
