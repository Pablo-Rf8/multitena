@extends('layout')
@section('title', __('Compartir credencial').' · '. $credential->title)
@section('page-css', 'credentials-sharing')
@section('content')
<section class="panel">
<p class="eyebrow">{{ $credential->title }}</p>
<h1>{{ __('Compartir credencial') }}</h1>
@include('credentials._nav')
<h2>{{ __('Compartir en solo lectura') }}</h2><p class="muted">{{ __('Definí un límite de aperturas, una duración o ambos. Mostrar contraseñas requiere autorización separada.') }}</p>
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
</section>
@endsection
