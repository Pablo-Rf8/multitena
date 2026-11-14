@extends('layout')
@section('title', ($contact ? __('Editar contacto') : __('Agregar contacto')).' · Multitena')
@section('page-css', 'crm-form')
@section('content')
<section class="panel crm-form-page">
    <a class="back-link" href="{{ $contact ? route('contacts.show', $contact) : route('contacts.index') }}">← {{ __('Volver a contactos') }}</a>
    <div class="section-head"><div><p class="eyebrow">CRM / {{ __('Contactos') }}</p><h1>{{ $contact ? __('Editar contacto') : __('Agregar contacto') }}</h1><p class="intro">{{ __('Completa los datos para registrar una persona.') }}</p></div></div>
    <form method="post" action="{{ $contact ? route('contacts.update', $contact) : route('contacts.store') }}" class="crm-form">
        @csrf @if($contact) @method('PUT') @endif
        <div class="crm-fields">
            <div class="crm-field"><label for="name">{{ __('Nombre') }}</label><input id="name" name="name" value="{{ old('name', $contact?->name) }}" maxlength="120" placeholder="{{ __('Ej. Ana Morales') }}" required autofocus>@error('name')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
            <div class="crm-field"><label for="organization">{{ __('Organización') }}</label><input id="organization" name="organization" value="{{ old('organization', $contact?->organization) }}" maxlength="120" placeholder="{{ __('Ej. Distribuidora Norte') }}">@error('organization')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
            <div class="crm-field"><label for="email">{{ __('Correo') }}</label><input id="email" name="email" type="email" value="{{ old('email', $contact?->email) }}" maxlength="255" placeholder="correo@ejemplo.com">@error('email')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
            <div class="crm-field"><label for="phone">{{ __('Teléfono') }}</label><input id="phone" name="phone" type="tel" value="{{ old('phone', $contact?->phone) }}" maxlength="40" placeholder="5555 0000">@error('phone')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
            <div class="crm-field"><label for="status">{{ __('Estado') }}</label><select id="status" name="status" required>@foreach(['prospect' => 'Prospecto', 'qualified' => 'Calificado', 'customer' => 'Cliente', 'inactive' => 'Inactivo'] as $key => $label)<option value="{{ $key }}" @selected(old('status', $contact?->status ?? 'prospect') === $key)>{{ __($label) }}</option>@endforeach</select>@error('status')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
            <div class="crm-field"><label for="source">{{ __('Origen') }}</label><input id="source" name="source" value="{{ old('source', $contact?->source) }}" maxlength="80" placeholder="{{ __('Ej. recomendación, sitio web') }}">@error('source')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
            @if(auth()->user()->role === 'company_admin')
                <div class="crm-field">
                    <label for="branch_id">{{ __('Sucursal responsable') }}</label>
                    <select id="branch_id" name="branch_id" required>
                        <option value="">{{ __('Seleccioná una sucursal') }}</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected(old('branch_id', $contact?->branch_id) == $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                    @error('branch_id')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                    @if($contact)<p class="hint">{{ __('Al cambiar la sucursal, sus oportunidades se trasladarán con el contacto.') }}</p>@endif
                </div>
            @else
                <p class="hint">{{ __('Se guardará en tu sucursal:') }} {{ auth()->user()->branch->name }}.</p>
            @endif
        </div>
        <div class="form-actions"><button class="button button-primary" type="submit">{{ $contact ? __('Guardar cambios') : __('Crear contacto') }}</button><a class="button button-ghost" href="{{ $contact ? route('contacts.show', $contact) : route('contacts.index') }}">{{ __('Cancelar') }}</a></div>
    </form>
</section>
@endsection
