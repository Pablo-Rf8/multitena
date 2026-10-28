@extends('layout')

@section('title', 'Nueva empresa · Multitena')

@section('content')
<section class="panel form-panel">
    <a class="back-link" href="{{ route('dashboard') }}">← Volver a empresas</a>
    <p class="eyebrow">Nueva empresa</p>
    <h1>Crear empresa</h1>
    <p class="intro">Se crearán la sucursal Central y su administrador en una sola operación.</p>
    <form method="post" action="{{ route('companies.store') }}" class="form-stack" data-company-form>
        @csrf
        <label for="name">Nombre de la empresa</label>
        <input id="name" name="name" type="text" maxlength="120" value="{{ old('name') }}" required autofocus data-company-name>
        @error('name') <p class="field-error" role="alert">{{ $message }}</p> @enderror

        <label for="currency">Moneda de las oportunidades</label>
        <select id="currency" name="currency" required>
            @foreach(['GTQ'=>'Quetzal (GTQ)','USD'=>'Dólar (USD)','MXN'=>'Peso mexicano (MXN)','EUR'=>'Euro (EUR)','COP'=>'Peso colombiano (COP)','CRC'=>'Colón costarricense (CRC)'] as $code => $label)
                <option value="{{ $code }}" @selected(old('currency', 'GTQ') === $code)>{{ $label }}</option>
            @endforeach
        </select>
        @error('currency') <p class="field-error" role="alert">{{ $message }}</p> @enderror

        <label for="admin_email">Correo del administrador</label>
        <input id="admin_email" name="admin_email" type="email" maxlength="255" value="{{ old('admin_email') }}" placeholder="admin@empresa.com" data-admin-email>
        <p class="hint">Se sugiere a partir del nombre. Podés cambiarlo si el dominio es distinto.</p>
        @error('admin_email') <p class="field-error" role="alert">{{ $message }}</p> @enderror

        <label for="admin_password">Contraseña inicial del administrador</label>
        <input id="admin_password" name="admin_password" type="password" minlength="12" autocomplete="new-password" required>
        <p class="hint">Mínimo 12 caracteres. Compartila con el administrador por un medio seguro.</p>
        @error('admin_password') <p class="field-error" role="alert">{{ $message }}</p> @enderror

        <label for="admin_password_confirmation">Confirmar contraseña</label>
        <input id="admin_password_confirmation" name="admin_password_confirmation" type="password" minlength="12" autocomplete="new-password" required>
        <button class="button" type="submit">Crear empresa y sucursal</button>
    </form>
</section>
@endsection
