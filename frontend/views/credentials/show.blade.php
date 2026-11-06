@extends('layout')
@section('title', $credential->title.' · Multitena')
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
<div class="credential-sharing"><h2>{{ __('Compartir en solo lectura') }}</h2><p class="muted">{{ __('Definí un límite de aperturas, una duración o ambos. Mostrar contraseñas requiere autorización separada.') }}</p>
@if($errors->any())<div class="notice notice-error" role="alert">{{ $errors->first() }}</div>@endif
@if($users->isNotEmpty() || $superAdmins->isNotEmpty())<form method="post" action="{{ route('credentials.shares.store', $credential) }}" class="credential-share-form">@csrf
<label>{{ __('Destinatario') }} <select name="user_id" required><option value="">{{ __('Elegir usuario') }}</option>
@if($users->isNotEmpty())<optgroup label="{{ __('Usuarios de la empresa') }}">@foreach($users as $user)<option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->name }} ({{ $user->email }})</option>@endforeach</optgroup>@endif
@if($superAdmins->isNotEmpty())<optgroup label="{{ __('Superadministradores de plataforma') }}">@foreach($superAdmins as $superAdmin)<option value="{{ $superAdmin->id }}" @selected(old('user_id') == $superAdmin->id)>{{ $superAdmin->name }} ({{ $superAdmin->email }})</option>@endforeach</optgroup>@endif
</select></label>
<label>{{ __('Máximo de aperturas') }} <input type="number" name="max_accesses" min="1" max="100000" value="{{ old('max_accesses') }}" placeholder="{{ __('Sin límite') }}"></label>
<label>{{ __('Duración') }} <input type="number" name="duration_value" min="1" max="365" value="{{ old('duration_value') }}" placeholder="{{ __('Opcional') }}"></label>
<label>{{ __('Unidad') }} <select name="duration_unit"><option value="">{{ __('Sin vencimiento') }}</option><option value="hours" @selected(old('duration_unit') === 'hours')>{{ __('Horas') }}</option><option value="days" @selected(old('duration_unit') === 'days')>{{ __('Días') }}</option><option value="weeks" @selected(old('duration_unit') === 'weeks')>{{ __('Semanas') }}</option><option value="months" @selected(old('duration_unit') === 'months')>{{ __('Meses') }}</option></select></label>
<label class="credential-check"><input type="checkbox" name="can_reveal" value="1" @checked(old('can_reveal'))> {{ __('Permitir mostrar y copiar contraseñas') }}</label>
<button class="button button-primary" type="submit">{{ __('Compartir credencial') }}</button></form>@endif
@if($credential->shares->isNotEmpty())<div class="table-scroll"><table><thead><tr><th>{{ __('Destinatario') }}</th><th>{{ __('Aperturas') }}</th><th>{{ __('Vence') }}</th><th>{{ __('Estado') }}</th><th>{{ __('Acción') }}</th></tr></thead><tbody>
@foreach($credential->shares->sortByDesc('created_at') as $share)
@php($shareStatus = $share->revoked_at ? __('Revocado') : ($share->expires_at?->isPast() ? __('Vencido') : ($share->max_accesses !== null && $share->used_accesses >= $share->max_accesses ? __('Agotado') : __('Activo'))))
<tr><td>{{ $share->user->name }}</td><td>{{ $share->used_accesses }} / {{ $share->max_accesses ?? __('Sin límite') }}@if($share->max_accesses !== null) · {{ max(0, $share->max_accesses - $share->used_accesses) }} {{ __('restantes') }}@endif</td><td>{{ $share->expires_at?->format('d/m/Y H:i') ?? __('Sin vencimiento') }}</td><td>{{ $shareStatus }}</td><td>@if(!$share->revoked_at)<form method="post" action="{{ route('credentials.shares.destroy', [$credential, $share]) }}">@csrf @method('DELETE')<button class="button button-ghost button-sm" type="submit" data-confirm="{{ __('¿Revocar este acceso?') }}">{{ __('Revocar') }}</button></form>@endif</td></tr>
@endforeach
</tbody></table></div>@endif
<h3>{{ __('Historial de aperturas') }}</h3>
@if($accesses->count() === 0)<p class="muted">{{ __('Todavía no hay aperturas.') }}</p>@else<div class="table-scroll"><table><thead><tr><th>{{ __('Usuario') }}</th><th>{{ __('Fecha') }}</th></tr></thead><tbody>@foreach($accesses as $access)<tr><td>{{ $access->user?->name ?? __('Usuario eliminado') }}</td><td>{{ $access->opened_at->format('d/m/Y H:i:s') }}</td></tr>@endforeach</tbody></table></div>{{ $accesses->links('pagination.simple') }}@endif
<details class="credential-legacy"><summary>{{ __('Permisos anteriores') }}</summary><p class="muted">{{ __('Los permisos existentes se conservan. Los accesos nuevos de arriba son solo lectura.') }}</p>
@if($users->isNotEmpty())<form method="post" action="{{ route('credentials.grants.store', $credential) }}" class="filter-bar">@csrf
<select name="user_id" aria-label="{{ __('Usuario') }}" required><option value="">{{ __('Elegir usuario') }}</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>@endforeach</select>
<select name="level" aria-label="{{ __('Nivel de acceso') }}"><option value="view">{{ __('Ver') }}</option><option value="edit">{{ __('Modificable') }}</option></select>
<label class="credential-check"><input type="checkbox" name="can_reveal" value="1"> {{ __('Permitir mostrar y copiar contraseñas') }}</label>
<button class="button button-primary" type="submit">{{ __('Guardar permiso') }}</button></form>@endif
@if($credential->grants->isNotEmpty())<div class="table-scroll"><table><thead><tr><th>{{ __('Usuario') }}</th><th>{{ __('Acceso') }}</th><th>{{ __('Contraseñas') }}</th><th>{{ __('Acción') }}</th></tr></thead><tbody>
@foreach($credential->grants as $grant)<tr><td>{{ $grant->user->name }}</td><td>{{ $grant->level === 'edit' ? __('Modificable') : __('Ver') }}</td><td>{{ $grant->can_reveal ? __('Autorizado') : __('Ocultas') }}</td><td><form method="post" action="{{ route('credentials.grants.destroy', [$credential, $grant->user]) }}">@csrf @method('DELETE')<button class="button button-ghost button-sm" type="submit" data-confirm="{{ __('¿Revocar este acceso?') }}">{{ __('Revocar') }}</button></form></td></tr>@endforeach
</tbody></table></div>@endif</details></div>
<form method="post" action="{{ route('credentials.destroy', $credential) }}">@csrf @method('DELETE')<button class="button button-ghost" type="submit" data-confirm="{{ __('¿Eliminar esta credencial?') }}">{{ __('Eliminar credencial') }}</button></form>
@endif
</section>
@endsection
