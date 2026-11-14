@extends('layout')
@section('title', __('Sesiones web y móviles'))
@section('content')
<section class="panel">
<p class="eyebrow">{{ __('Equipo') }}</p>
<h1>{{ __('Sesiones web y móviles') }}</h1>
<p class="intro">{{ __('Consultá y finalizá sesiones activas.') }}</p>
@include('workforce._nav')
@if($errors->any())<div class="notice notice-error" role="alert">{{ $errors->first() }}</div>@endif
@forelse($sessions as $accessSession)
                <div class="workforce-item"><strong>{{ $accessSession->user?->name ?? __('Usuario eliminado') }}</strong><p class="muted">{{ $accessSession->channel === 'web' ? __('Navegador web') : ($accessSession->device?->name ?? __('Móvil')) }} · {{ $accessSession->status === 'active' ? __('Activa') : __('Finalizada') }}</p>
                    <p class="muted">{{ __('Inicio:') }} {{ $accessSession->started_at }} · {{ __('Último acceso:') }} {{ $accessSession->last_seen_at ?? '—' }} @if($accessSession->ended_at)· {{ __('Cierre:') }} {{ $accessSession->ended_at }}@endif @if($accessSession->end_reason)· {{ __('Motivo:') }} {{ $accessSession->end_reason }}@endif</p>
                    @if($accessSession->status === 'active')<form method="post" action="{{ route('workforce.sessions.end', $accessSession) }}" data-confirm="{{ __('¿Finalizar esta sesión?') }}">@csrf @method('PATCH')<button class="button button-danger button-sm" type="submit">{{ __('Finalizar sesión') }}</button></form>@endif
                </div>
            @empty<p class="empty-state">{{ __('Aún no hay sesiones.') }}</p>@endforelse
{{ $sessions->links('pagination.simple') }}
</section>
@endsection
