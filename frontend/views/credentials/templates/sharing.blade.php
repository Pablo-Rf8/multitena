@extends('layout')
@section('title', __('Compartir plantilla').' · '. $template->name)
@section('page-css', 'credential-templates-sharing')
@section('content')
<section class="panel">
    <a class="back-link" href="{{ route('credential-templates.show', $template) }}">← {{ __('Volver a campos') }}</a>
    <p class="eyebrow">{{ __('Plantilla vacía') }}</p>
    <h1>{{ $template->name }}</h1>
    @if($errors->any())<div class="notice notice-error" role="alert">{{ $errors->first() }}</div>@endif
<div class="credential-sharing"><h2>{{ __('Compartir plantilla vacía') }}</h2><p class="muted">{{ __('El destinatario solo verá esta estructura, sin credenciales guardadas.') }}</p>
        @if($users->isNotEmpty())<form method="post" action="{{ route('credential-templates.shares.store', $template) }}" class="credential-share-form">@csrf
            <label>{{ __('Destinatario') }} <select name="user_id" required><option value="">{{ __('Elegir usuario') }}</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->name }} ({{ $user->email }})</option>@endforeach</select></label>
            <label>{{ __('Máximo de aperturas') }} <input type="number" name="max_accesses" min="1" max="100000" value="{{ old('max_accesses') }}" placeholder="{{ __('Sin límite') }}"></label>
            <label>{{ __('Duración') }} <input type="number" name="duration_value" min="1" max="365" value="{{ old('duration_value') }}" placeholder="{{ __('Opcional') }}"></label>
            <label>{{ __('Unidad') }} <select name="duration_unit"><option value="">{{ __('Sin vencimiento') }}</option><option value="hours" @selected(old('duration_unit') === 'hours')>{{ __('Horas') }}</option><option value="days" @selected(old('duration_unit') === 'days')>{{ __('Días') }}</option><option value="weeks" @selected(old('duration_unit') === 'weeks')>{{ __('Semanas') }}</option><option value="months" @selected(old('duration_unit') === 'months')>{{ __('Meses') }}</option></select></label>
            <button class="button button-primary" type="submit">{{ __('Compartir plantilla') }}</button>
        </form>@endif
        @if($shares->isNotEmpty())<div class="table-scroll"><table><thead><tr><th>{{ __('Destinatario') }}</th><th>{{ __('Aperturas') }}</th><th>{{ __('Vence') }}</th><th>{{ __('Estado') }}</th><th>{{ __('Acción') }}</th></tr></thead><tbody>
        @foreach($shares as $share)
            @php($status = $share->revoked_at ? __('Revocado') : ($share->expires_at?->isPast() ? __('Vencido') : ($share->max_accesses !== null && $share->used_accesses >= $share->max_accesses ? __('Agotado') : __('Activo'))))
            <tr><td>{{ $share->user->name }}</td><td>{{ $share->used_accesses }} / {{ $share->max_accesses ?? __('Sin límite') }}</td><td>{{ $share->expires_at?->format('d/m/Y H:i') ?? __('Sin vencimiento') }}</td><td>{{ $status }}</td><td>@if(!$share->revoked_at)<form method="post" action="{{ route('credential-templates.shares.destroy', [$template, $share]) }}">@csrf @method('DELETE')<button class="button button-ghost button-sm" type="submit" data-confirm="{{ __('¿Revocar este acceso?') }}">{{ __('Revocar') }}</button></form>@endif</td></tr>
        @endforeach
        </tbody></table></div>@endif</div>
</section>
@endsection
