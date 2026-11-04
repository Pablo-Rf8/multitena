@extends('layout')
@section('title', __('Notificaciones'))
@section('content')
<section class="page-heading"><p class="eyebrow">{{ __('Acceso') }}</p><h1>{{ __('Notificaciones') }}</h1></section>
<section class="panel">@forelse($notices as $notice)
<div class="workforce-item"><strong>{{ $notice->title ?: $notice->kind }}</strong><p>{{ $notice->body ?: 'Solicitud o cambio de acceso.' }} · {{ $notice->created_at }}</p>
@if(!$notice->read_at)<form method="post" action="{{ route('notifications.read', $notice->id) }}">@csrf @method('PATCH')<button>{{ __('Marcar como leída') }}</button></form>@else<span class="muted">{{ __('Leída') }}</span>@endif</div>
@empty<p class="empty-state">{{ __('No hay notificaciones.') }}</p>@endforelse
{{ $notices->links() }}</section>
@endsection
