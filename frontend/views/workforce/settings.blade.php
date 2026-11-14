@extends('layout')
@section('title', __('Configuración de acceso'))
@section('page-css', 'workforce-settings')
@section('content')
<section class="panel">
<p class="eyebrow">{{ __('Equipo') }}</p>
<h1>{{ __('Configuración de acceso') }}</h1>
<p class="intro">{{ __('Dominios, empleados y seguridad de dispositivos.') }}</p>
@include('workforce._nav')
<div class="settings-code"><span>{{ __('Código de empresa') }}</span><strong>{{ auth()->user()->company->code }}</strong></div>
@if($errors->any())<div class="notice notice-error" role="alert">{{ $errors->first() }}</div>@endif
<form method="post" action="{{ route('workforce.settings') }}" class="settings-form">
    @csrf @method('PUT')
    <div class="settings-fields">
        <label class="settings-field settings-field-wide"><span>{{ __('Dominios de correo autorizados') }}</span><small>{{ __('Sepáralos por coma.') }}</small><input name="email_domains" value="{{ implode(', ', $settings->email_domains ?? []) }}" placeholder="empresa.com"></label>
        <label class="settings-field"><span>{{ __('Acceso de empleados') }}</span><select name="employee_access_enabled"><option value="1" @selected($settings->employee_access_enabled)>{{ __('Habilitado') }}</option><option value="0" @selected(!$settings->employee_access_enabled)>{{ __('Deshabilitado') }}</option></select></label>
        <label class="settings-field"><span>{{ __('Aprobación de nuevos dispositivos') }}</span><select name="require_device_approval"><option value="0" @selected(!$settings->require_device_approval)>{{ __('No') }}</option><option value="1" @selected($settings->require_device_approval)>{{ __('Sí') }}</option></select></label>
        <label class="settings-field"><span>{{ __('Duración de sesión móvil (horas)') }}</span><input type="number" name="token_lifetime_hours" min="1" max="2160" value="{{ $settings->token_lifetime_hours ?? 720 }}"></label>
    </div>
    <div class="settings-actions"><button class="button button-primary" type="submit">{{ __('Guardar configuración') }}</button></div>
</form>
</section>
@endsection
