@extends('layout')
@section('title', __('Permisos').' · '. $credential->title)
@section('page-css', 'credentials-permissions')
@section('content')
<section class="panel">
<p class="eyebrow">{{ $credential->title }}</p>
<h1>{{ __('Permisos') }}</h1>
@include('credentials._nav')
<p class="muted">{{ __('Asigná permisos permanentes de lectura o edición. Para limitar las aperturas o la duración, usá Compartir.') }}</p>
@if($users->isNotEmpty())<form method="post" action="{{ route('credentials.grants.store', $credential) }}" class="credential-permission-form">@csrf
<div class="credential-permission-fields">
<label><span>{{ __('Usuario') }}</span><select name="user_id" required><option value="">{{ __('Elegir usuario') }}</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>@endforeach</select></label>
<label><span>{{ __('Nivel de acceso') }}</span><select name="level"><option value="view">{{ __('Ver') }}</option><option value="edit">{{ __('Modificable') }}</option></select></label>
</div>
<label class="credential-check"><input type="checkbox" name="can_reveal" value="1"> {{ __('Permitir mostrar y copiar contraseñas') }}</label>
<button class="button button-primary" type="submit">{{ __('Guardar permiso') }}</button></form>@endif
@if($credential->grants->isNotEmpty())<div class="table-scroll"><table><thead><tr><th>{{ __('Usuario') }}</th><th>{{ __('Acceso') }}</th><th>{{ __('Contraseñas') }}</th><th>{{ __('Acción') }}</th></tr></thead><tbody>
@foreach($credential->grants as $grant)<tr><td>{{ $grant->user->name }}</td><td>{{ $grant->level === 'edit' ? __('Modificable') : __('Ver') }}</td><td>{{ $grant->can_reveal ? __('Autorizado') : __('Ocultas') }}</td><td><form method="post" action="{{ route('credentials.grants.destroy', [$credential, $grant->user]) }}">@csrf @method('DELETE')<button class="button button-ghost button-sm" type="submit" data-confirm="{{ __('¿Revocar este acceso?') }}">{{ __('Revocar') }}</button></form></td></tr>@endforeach
</tbody></table></div>@endif
</section>
@endsection
