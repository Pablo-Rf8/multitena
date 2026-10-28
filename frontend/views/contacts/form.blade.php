@extends('layout')

@section('title', ($contact ? 'Editar' : 'Nuevo').' contacto · Multitena')

@section('content')
<section class="panel form-panel">
    <a class="back-link" href="{{ $contact ? route('contacts.show', $contact) : route('contacts.index') }}">← Volver a contactos</a>
    <p class="eyebrow">CRM</p>
    <h1>{{ $contact ? 'Editar contacto' : 'Agregar contacto' }}</h1>
    <form method="post" action="{{ $contact ? route('contacts.update', $contact) : route('contacts.store') }}" class="form-stack">
        @csrf @if($contact) @method('PUT') @endif
        <label for="name">Nombre</label>
        <input id="name" name="name" value="{{ old('name', $contact?->name) }}" maxlength="120" required autofocus>
        @error('name')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        <label for="organization">Organización</label>
        <input id="organization" name="organization" value="{{ old('organization', $contact?->organization) }}" maxlength="120">
        @error('organization')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        <label for="email">Correo</label>
        <input id="email" name="email" type="email" value="{{ old('email', $contact?->email) }}" maxlength="255">
        @error('email')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        <label for="phone">Teléfono</label>
        <input id="phone" name="phone" value="{{ old('phone', $contact?->phone) }}" maxlength="40">
        @error('phone')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        <label for="status">Estado</label>
        <select id="status" name="status" required>
            @foreach(['prospect' => 'Prospecto', 'qualified' => 'Calificado', 'customer' => 'Cliente', 'inactive' => 'Inactivo'] as $key => $label)
                <option value="{{ $key }}" @selected(old('status', $contact?->status ?? 'prospect') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        <label for="source">Origen</label>
        <input id="source" name="source" value="{{ old('source', $contact?->source) }}" maxlength="80" placeholder="Ej. recomendación, sitio web">
        @error('source')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        @if(auth()->user()->role === 'company_admin')
            <label for="branch_id">Sucursal responsable</label>
            <select id="branch_id" name="branch_id" required>
                <option value="">Seleccioná una sucursal</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" @selected(old('branch_id', $contact?->branch_id) == $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
            @error('branch_id')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            @if($contact)<p class="hint">Si cambiás la sucursal, sus oportunidades se trasladarán con el contacto.</p>@endif
        @else
            <p class="hint">Se guardará en tu sucursal: {{ auth()->user()->branch->name }}.</p>
        @endif
        <button class="button" type="submit">{{ $contact ? 'Guardar cambios' : 'Crear contacto' }}</button>
    </form>
</section>
@endsection
