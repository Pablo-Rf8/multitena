@extends('layout')
@section('title', __('Dispositivos'))
@section('content')
<section class="panel">
<p class="eyebrow">{{ __('Equipo') }}</p>
<h1>{{ __('Dispositivos') }}</h1>
<p class="intro">{{ __('Revisá y autorizá los dispositivos de tu empresa.') }}</p>
@include('workforce._nav')
@if($errors->any())<div class="notice notice-error" role="alert">{{ $errors->first() }}</div>@endif
@forelse($devices as $device)
                <div class="workforce-item"><strong>{{ $device->name }}</strong><p class="muted">{{ $device->user?->name ?? __('Usuario eliminado') }} · {{ ['pending' => __('Pendiente'), 'approved' => __('Autorizado'), 'revoked' => __('Revocado')][$device->status] ?? $device->status }}</p>
                    @if($device->status !== 'revoked')<form method="post" action="{{ route('workforce.device', $device) }}" class="actions">@csrf @method('PATCH')
                        @if($device->status === 'pending')<button class="button button-primary button-sm" name="status" value="approved">{{ __('Autorizar') }}</button>@endif
                        <button class="button button-danger button-sm" name="status" value="revoked" data-confirm="{{ __('¿Revocar este dispositivo y sus sesiones activas?') }}">{{ __('Revocar') }}</button>
                    </form>@endif
                </div>
            @empty<p class="empty-state">{{ __('Aún no hay dispositivos registrados.') }}</p>@endforelse
{{ $devices->links('pagination.simple') }}
</section>
@endsection
