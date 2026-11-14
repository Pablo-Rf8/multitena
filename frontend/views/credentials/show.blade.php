@extends('layout')
@section('title', $credential->title.' · Multitena')
@section('page-js', 'credentials-show')
@section('page-css', 'credentials-show')
@section('content')
<section class="panel"><div class="section-head"><div><p class="eyebrow">{{ __('Gestión de Credenciales') }}</p><h1>{{ $credential->title }}</h1><p class="intro">@if(auth()->user()->role === 'super_admin'){{ $credential->company->name }} · @endif{{ $credential->template->name }} · {{ $credential->visibility === 'private' ? __('Privada') : __('Compartida') }} · {{ __('Creada por') }} {{ $credential->owner->name }}</p></div>
@if($canEdit)<a class="button button-primary" href="{{ route('credentials.edit', $credential) }}">{{ __('Editar credencial') }}</a>@endif</div>
<div class="credential-row-actions"><a class="button button-ghost" href="{{ route('credentials.index') }}">← {{ __('Volver a credenciales') }}</a>
@unless(auth()->user()->role === 'super_admin')<form method="post" action="{{ $isFavorite ? route('credentials.favorite.destroy', $credential) : route('credentials.favorite.store', $credential) }}">@csrf @if($isFavorite) @method('DELETE') @endif<button class="button button-ghost" type="submit">{{ $isFavorite ? __('★ Quitar de favoritas') : __('☆ Agregar a favoritas') }}</button></form>@endunless</div>
<div class="credential-details">
@foreach($credential->template->fields as $field)
@php($stored = $values->get($field->id))
<div class="credential-detail"><span class="muted">{{ $field->name }}</span>
@if($field->type === 'password')
    <div class="credential-secret"><strong data-secret-output>••••••••</strong>@if($canReveal && $stored?->secret_ciphertext)<button class="button button-ghost button-sm" type="button" data-reveal-url="{{ route('credentials.reveal', [$credential, $field]) }}" data-label-show="{{ __('Mostrar') }}" data-label-hide="{{ __('Ocultar') }}" data-error-reveal="{{ __('No se pudo mostrar la contraseña.') }}" data-error-copy="{{ __('No se pudo copiar.') }}">{{ __('Mostrar') }}</button><button class="button button-ghost button-sm" type="button" data-copy-secret data-label-copy="{{ __('Copiar') }}" data-label-copied="{{ __('Copiado') }}" disabled>{{ __('Copiar') }}</button>@endif</div>
@elseif($field->type === 'url' && $stored?->value)<a href="{{ $stored->value }}" target="_blank" rel="noopener noreferrer">{{ $stored->value }}</a>
@else <strong>{{ $stored?->value ?: '—' }}</strong>@endif
</div>@endforeach
</div>
@if($canShare)
<div class="module-links">
    <a href="{{ route('credentials.sharing', $credential) }}"><strong>{{ __('Compartir en solo lectura') }}</strong><span>{{ __('Destinatarios, duración y límites de acceso') }}</span></a>
    <a href="{{ route('credentials.permissions', $credential) }}"><strong>{{ __('Permisos anteriores') }}</strong><span>{{ __('Ver y modificar según autorización') }}</span></a>
    <a href="{{ route('credentials.history', $credential) }}"><strong>{{ __('Historial de aperturas') }}</strong><span>{{ __('Quién abrió esta credencial y cuándo') }}</span></a>
</div>
@endif

@if($canShare)<form method="post" action="{{ route('credentials.destroy', $credential) }}">@csrf @method('DELETE')<button class="button button-ghost" type="submit" data-confirm="{{ __('¿Eliminar esta credencial?') }}">{{ __('Eliminar credencial') }}</button></form>@endif

</section>
@endsection
