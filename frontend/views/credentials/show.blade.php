@extends('layout')
@section('title', $credential->title.' · Multitena')
@section('content')
<section class="panel"><div class="section-head"><div><p class="eyebrow">{{ __('Gestión de Credenciales') }}</p><h1>{{ $credential->title }}</h1><p class="intro">{{ $credential->template->name }} · {{ $credential->visibility === 'private' ? __('Privada') : __('Compartida') }} · {{ __('Creada por') }} {{ $credential->owner->name }}</p></div>
@if($canEdit)<a class="button button-primary" href="{{ route('credentials.edit', $credential) }}">{{ __('Editar credencial') }}</a>@endif</div>
<p><a class="button button-ghost" href="{{ route('credentials.index') }}">← {{ __('Volver a credenciales') }}</a></p>
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
<div class="credential-sharing"><h2>{{ __('Permisos') }}</h2><p class="muted">{{ __('Dar acceso no autoriza a mostrar contraseñas. Activá esa opción por separado.') }}</p>
@if($errors->any())<div class="notice notice-error" role="alert">{{ $errors->first() }}</div>@endif
@if($users->isNotEmpty())<form method="post" action="{{ route('credentials.grants.store', $credential) }}" class="filter-bar">@csrf
<select name="user_id" aria-label="{{ __('Usuario') }}" required><option value="">{{ __('Elegir usuario') }}</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>@endforeach</select>
<select name="level" aria-label="{{ __('Nivel de acceso') }}"><option value="view">{{ __('Ver') }}</option><option value="edit">{{ __('Modificable') }}</option></select>
<label class="credential-check"><input type="checkbox" name="can_reveal" value="1"> {{ __('Permitir mostrar y copiar contraseñas') }}</label>
<button class="button button-primary" type="submit">{{ __('Guardar permiso') }}</button></form>@endif
@if($credential->grants->isNotEmpty())<div class="table-scroll"><table><thead><tr><th>{{ __('Usuario') }}</th><th>{{ __('Acceso') }}</th><th>{{ __('Contraseñas') }}</th><th>{{ __('Acción') }}</th></tr></thead><tbody>
@foreach($credential->grants as $grant)<tr><td>{{ $grant->user->name }}</td><td>{{ $grant->level === 'edit' ? __('Modificable') : __('Ver') }}</td><td>{{ $grant->can_reveal ? __('Autorizado') : __('Ocultas') }}</td><td><form method="post" action="{{ route('credentials.grants.destroy', [$credential, $grant->user]) }}">@csrf @method('DELETE')<button class="button button-ghost button-sm" type="submit" data-confirm="{{ __('¿Revocar este acceso?') }}">{{ __('Revocar') }}</button></form></td></tr>@endforeach
</tbody></table></div>@endif</div>
<form method="post" action="{{ route('credentials.destroy', $credential) }}">@csrf @method('DELETE')<button class="button button-ghost" type="submit" data-confirm="{{ __('¿Eliminar esta credencial?') }}">{{ __('Eliminar credencial') }}</button></form>
@endif
</section>
@endsection
