@extends('layout')

@section('title', 'Editar empresa · Multitena')

@section('content')
<section class="panel form-panel">
    <a class="back-link" href="{{ route('companies.show', $company) }}">← Volver a la empresa</a>
    <p class="eyebrow">Empresa</p>
    <h1>Editar {{ $company->name }}</h1>
    <p class="intro">El identificador {{ $company->slug }} se conserva para mantener referencias existentes.</p>
    <form method="post" action="{{ route('companies.update', $company) }}" class="form-stack">
        @csrf @method('PUT')
        <label for="name">Nombre</label>
        <input id="name" name="name" value="{{ old('name', $company->name) }}" maxlength="120" required autofocus>
        @error('name')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        <label for="currency">Moneda de las oportunidades</label>
        <select id="currency" name="currency" required>
            @foreach(['GTQ'=>'Quetzal (GTQ)','USD'=>'Dólar (USD)','MXN'=>'Peso mexicano (MXN)','EUR'=>'Euro (EUR)','COP'=>'Peso colombiano (COP)','CRC'=>'Colón costarricense (CRC)'] as $code => $label)
                <option value="{{ $code }}" @selected(old('currency', $company->currency) === $code)>{{ $label }}</option>
            @endforeach
        </select>
        @error('currency')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        <label for="contact_email">Correo de contacto</label>
        <input id="contact_email" name="contact_email" type="email" value="{{ old('contact_email', $company->contact_email) }}" maxlength="255">
        @error('contact_email')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        <label for="phone">Teléfono</label>
        <input id="phone" name="phone" value="{{ old('phone', $company->phone) }}" maxlength="40">
        @error('phone')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        <button class="button" type="submit">Guardar cambios</button>
    </form>
</section>
@endsection
