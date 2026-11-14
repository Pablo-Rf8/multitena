@extends('layout')
@section('title', __('Nueva empresa').' · Multitena')
@section('page-css', 'companies-create')
@section('page-js', 'companies-create')
@section('content')
<section class="panel company-create-page">
    <a class="back-link" href="{{ route('dashboard') }}">← {{ __('Volver a empresas') }}</a>
    <div class="section-head"><div><p class="eyebrow">{{ __('Plataforma') }} / {{ __('Empresas') }}</p><h1>{{ __('Crear empresa') }}</h1><p class="intro">{{ __('Se crearán la sucursal Central y el administrador inicial.') }}</p></div></div>
    <form method="post" action="{{ route('companies.store') }}" class="company-create-form" data-company-form>
        @csrf
        <div class="company-create-fields">
            <div class="company-create-field"><label for="name">{{ __('Nombre de la empresa') }}</label><input id="name" name="name" type="text" maxlength="120" value="{{ old('name') }}" placeholder="{{ __('Ej. Nueva empresa') }}" required autofocus data-company-name>@error('name')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
            <div class="company-create-field"><label for="currency">{{ __('Moneda de las oportunidades') }}</label><select id="currency" name="currency" required>@foreach(['GTQ'=>__('Quetzal (GTQ)'),'USD'=>__('Dólar (USD)'),'MXN'=>__('Peso mexicano (MXN)'),'EUR'=>'Euro (EUR)','COP'=>__('Peso colombiano (COP)'),'CRC'=>__('Colón costarricense (CRC)')] as $code => $label)<option value="{{ $code }}" @selected(old('currency', 'GTQ') === $code)>{{ $label }}</option>@endforeach</select>@error('currency')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
            <div class="company-create-field"><label for="admin_email">{{ __('Correo del administrador') }}</label><input id="admin_email" name="admin_email" type="email" maxlength="255" value="{{ old('admin_email') }}" placeholder="admin@empresa.com" data-admin-email><p class="hint">{{ __('Puedes cambiarlo si el dominio es distinto.') }}</p>@error('admin_email')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
            <div class="company-create-field"><label for="email_domains">{{ __('Dominio de correo para el acceso') }}</label><input id="email_domains" name="email_domains" value="{{ old('email_domains') }}" placeholder="empresa.com" maxlength="1000"><p class="hint">{{ __('Puedes agregar varios dominios separados por coma. Deben ser exclusivos de esta empresa.') }}</p>@error('email_domains')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
            <div class="company-create-field"><label for="admin_password">{{ __('Contraseña inicial del administrador') }}</label><input id="admin_password" name="admin_password" type="password" minlength="12" autocomplete="new-password" required><p class="hint">{{ __('Mínimo 12 caracteres. Compártela por un medio seguro.') }}</p>@error('admin_password')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
            <div class="company-create-field"><label for="admin_password_confirmation">{{ __('Confirmar contraseña') }}</label><input id="admin_password_confirmation" name="admin_password_confirmation" type="password" minlength="12" autocomplete="new-password" required></div>
        </div>
        <div class="company-create-note"><strong>{{ __('Qué se creará') }}</strong><span>{{ __('Empresa + sucursal Central + administrador inicial. El código se genera automáticamente.') }}</span></div>
        <div class="form-actions"><button class="button button-primary" type="submit">{{ __('Crear empresa y sucursal') }}</button><a class="button button-ghost" href="{{ route('dashboard') }}">{{ __('Cancelar') }}</a></div>
    </form>
</section>
@endsection
