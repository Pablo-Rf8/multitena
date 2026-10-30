@extends('layout')
@section('title', 'Notificaciones')
@section('content')
<section class="page-heading"><p class="eyebrow">Acceso</p><h1>Notificaciones</h1></section>
<section class="panel">@forelse($notices as $notice)
<div class="workforce-item"><strong>{{ $notice->title ?: $notice->kind }}</strong><p>{{ $notice->body ?: 'Solicitud o cambio de acceso.' }} · {{ $notice->created_at }}</p>
@if(!$notice->read_at)<form method="post" action="{{ route('notifications.read', $notice->id) }}">@csrf @method('PATCH')<button>Marcar como leída</button></form>@else<span class="muted">Leída</span>@endif</div>
@empty<p class="empty-state">No hay notificaciones.</p>@endforelse
{{ $notices->links() }}</section>
@endsection
