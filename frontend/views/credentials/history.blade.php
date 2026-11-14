@extends('layout')
@section('title', __('Historial de aperturas').' · '. $credential->title)
@section('content')
<section class="panel">
<p class="eyebrow">{{ $credential->title }}</p>
<h1>{{ __('Historial de aperturas') }}</h1>
@include('credentials._nav')
@if($accesses->count() === 0)<p class="muted">{{ __('Todavía no hay aperturas.') }}</p>@else<div class="table-scroll"><table><thead><tr><th>{{ __('Usuario') }}</th><th>{{ __('Fecha') }}</th></tr></thead><tbody>@foreach($accesses as $access)<tr><td>{{ $access->user?->name ?? __('Usuario eliminado') }}</td><td>{{ $access->opened_at->format('d/m/Y H:i:s') }}</td></tr>@endforeach</tbody></table></div>{{ $accesses->links('pagination.simple') }}@endif
</section>
@endsection
