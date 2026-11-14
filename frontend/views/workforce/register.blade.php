@extends('layout')
@section('title', __('Registrar empleado'))
@section('page-css', 'workforce-register')
@section('content')
<section class="panel workforce-register-page">
    <a class="back-link" href="{{ route('workforce.index') }}">← {{ __('Volver a empleados') }}</a>
    <p class="eyebrow">{{ __('Personal') }}</p><h1>{{ __('Registrar empleado') }}</h1>
    <p class="intro">{{ __('El acceso quedará pendiente de aprobación.') }}</p>
    @if($errors->any())<div class="notice notice-error" role="alert">{{ $errors->first() }}</div>@endif
    <form method="post" action="{{ route('workforce.employees.store') }}" class="workforce-register-form">@csrf
        <div class="workforce-register-fields">
            <label for="name">{{ __('Nombre') }}<input id="name" name="name" value="{{ old('name') }}" required></label>
            <label for="email">{{ __('Correo') }}<input id="email" type="email" name="email" value="{{ old('email') }}" required></label>
            <label for="phone">{{ __('Teléfono (opcional)') }}<input id="phone" type="tel" name="phone" maxlength="40" autocomplete="tel" value="{{ old('phone') }}"></label>
            <label for="branch_id">{{ __('Sucursal') }}<select id="branch_id" name="branch_id">@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select></label>
            <label for="password">{{ __('Contraseña temporal') }}<input id="password" type="password" name="password" minlength="12" required></label>
            <label for="password_confirmation">{{ __('Confirmar contraseña') }}<input id="password_confirmation" type="password" name="password_confirmation" minlength="12" required></label>
        </div>
        <div class="workforce-register-actions"><button class="button button-primary" type="submit">{{ __('Registrar empleado') }}</button><a class="button button-ghost" href="{{ route('workforce.index') }}">{{ __('Cancelar') }}</a></div>
    </form>
</section>
@endsection
