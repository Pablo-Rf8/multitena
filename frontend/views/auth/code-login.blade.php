@extends('layout')
@section('title', 'Código temporal · '.$company->name)
@section('content')
<section class="panel narrow login-panel">
    <p class="eyebrow">Acceso de empresa</p>
    <h1>{{ $company->name }}</h1>
    <p class="intro">Ingresá tu correo y el código de seis dígitos de tu enlace privado. Cada código dura 30 segundos y solo se puede usar una vez.</p>
    <form method="post" action="{{ route('login.code.submit') }}" class="form-stack">
        @csrf
        <label for="email">Correo electrónico</label>
        <input id="email" name="email" type="email" autocomplete="username" value="{{ old('email') }}" required autofocus>
        <label for="code">Código temporal</label>
        <input id="code" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required>
        @error('code')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        <button class="button" type="submit">Ingresar</button>
    </form>
    <div class="login-links"><a href="{{ route('login') }}">Ingresar con contraseña</a><a href="{{ route('login.change') }}">Cambiar empresa</a></div>
</section>
@endsection
