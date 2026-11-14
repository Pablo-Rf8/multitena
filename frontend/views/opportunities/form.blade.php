@extends('layout')
@section('title', ($opportunity ? __('Editar oportunidad') : __('Nueva oportunidad')).' · Multitena')
@section('page-css', 'crm-form')
@section('content')
<section class="panel crm-form-page">
    <a class="back-link" href="{{ route('contacts.show', $contact) }}">← {{ __('Volver a') }} {{ $contact->name }}</a>
    <div class="section-head"><div><p class="eyebrow">CRM / {{ __('Oportunidades') }}</p><h1>{{ $opportunity ? __('Editar oportunidad') : __('Nueva oportunidad') }}</h1><p class="intro">{{ __('Asociada a :name · Sucursal :branch', ['name' => $contact->name, 'branch' => $contact->branch->name]) }}</p></div></div>
    <form method="post" action="{{ $opportunity ? route('opportunities.update', $opportunity) : route('opportunities.store') }}" class="crm-form">
        @csrf @if($opportunity) @method('PUT') @else <input type="hidden" name="contact_id" value="{{ $contact->id }}"> @endif
        <div class="crm-fields">
            <div class="crm-field crm-field-wide"><label for="title">{{ __('Título') }}</label><input id="title" name="title" value="{{ old('title', $opportunity?->title) }}" maxlength="160" placeholder="{{ __('Ej. Renovación anual') }}" required autofocus>@error('title')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
            <div class="crm-field"><label for="amount">{{ __('Monto estimado') }} ({{ auth()->user()->company->currency }})</label><input id="amount" name="amount" type="number" step="0.01" min="0" max="999999999.99" value="{{ old('amount', $opportunity ? number_format($opportunity->amount_cents / 100, 2, '.', '') : '') }}" required>@error('amount')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
            <div class="crm-field"><label for="stage">{{ __('Etapa') }}</label><select id="stage" name="stage" required>@foreach(['new'=>__('Nueva'),'qualified'=>__('Calificada'),'proposal'=>__('Propuesta'),'won'=>__('Ganada'),'lost'=>__('Perdida')] as $key => $label)<option value="{{ $key }}" @selected(old('stage', $opportunity?->stage ?? 'new') === $key)>{{ $label }}</option>@endforeach</select>@error('stage')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
            <div class="crm-field"><label for="expected_close_on">{{ __('Cierre estimado') }}</label><input id="expected_close_on" name="expected_close_on" type="date" value="{{ old('expected_close_on', $opportunity?->expected_close_on?->format('Y-m-d')) }}">@error('expected_close_on')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
            <div class="crm-field crm-field-wide"><label for="description">{{ __('Descripción') }}</label><textarea id="description" name="description" rows="3" maxlength="5000" placeholder="{{ __('Notas sobre el negocio y próximos pasos') }}">{{ old('description', $opportunity?->description) }}</textarea>@error('description')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
        </div>
        <div class="form-actions"><button class="button button-primary" type="submit">{{ $opportunity ? __('Guardar cambios') : __('Crear oportunidad') }}</button><a class="button button-ghost" href="{{ route('contacts.show', $contact) }}">{{ __('Cancelar') }}</a></div>
    </form>
</section>
@endsection
