@extends('layout')

@section('title', ($opportunity ? 'Editar' : 'Nueva').' oportunidad · Multitena')

@section('content')
<section class="panel form-panel">
    <a class="back-link" href="{{ route('contacts.show', $contact) }}">← Volver a {{ $contact->name }}</a>
    <p class="eyebrow">CRM · {{ $contact->name }}</p>
    <h1>{{ $opportunity ? 'Editar oportunidad' : 'Nueva oportunidad' }}</h1>
    <p class="intro">La oportunidad pertenece a la sucursal del contacto.</p>
    <form method="post" action="{{ $opportunity ? route('opportunities.update', $opportunity) : route('opportunities.store') }}" class="form-stack">
        @csrf @if($opportunity) @method('PUT') @else <input type="hidden" name="contact_id" value="{{ $contact->id }}"> @endif
        <label for="title">Título</label>
        <input id="title" name="title" value="{{ old('title', $opportunity?->title) }}" maxlength="160" required autofocus>
        @error('title')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        <label for="amount">Monto estimado ({{ auth()->user()->company->currency }})</label>
        <input id="amount" name="amount" type="number" step="0.01" min="0" max="999999999.99" value="{{ old('amount', $opportunity ? number_format($opportunity->amount_cents / 100, 2, '.', '') : '') }}" required>
        @error('amount')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        <label for="stage">Etapa</label>
        <select id="stage" name="stage" required>
            @foreach(['new'=>'Nueva','qualified'=>'Calificada','proposal'=>'Propuesta','won'=>'Ganada','lost'=>'Perdida'] as $key => $label)
                <option value="{{ $key }}" @selected(old('stage', $opportunity?->stage ?? 'new') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        @error('stage')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        <label for="expected_close_on">Cierre estimado</label>
        <input id="expected_close_on" name="expected_close_on" type="date" value="{{ old('expected_close_on', $opportunity?->expected_close_on?->format('Y-m-d')) }}">
        @error('expected_close_on')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        <label for="description">Descripción</label>
        <textarea id="description" name="description" rows="4" maxlength="5000">{{ old('description', $opportunity?->description) }}</textarea>
        @error('description')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        <button class="button" type="submit">{{ $opportunity ? 'Guardar cambios' : 'Crear oportunidad' }}</button>
    </form>
</section>
@endsection
