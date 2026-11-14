@extends('layout')
@section('title', __('Editar perfil'))
@section('page-css', 'profile-edit')
@section('content')
<section class="panel profile-edit-page">
    <a class="back-link" href="{{ route('profile.show') }}">← {{ __('Volver a mi perfil') }}</a>
    <div class="section-head"><div><p class="eyebrow">{{ __('Cuenta') }} / {{ __('Perfil') }}</p><h1>{{ __('Editar perfil') }}</h1><p class="intro">{{ __('Actualiza los datos personales de tu cuenta.') }}</p></div></div>
    @if($errors->any())<div class="notice notice-error" role="alert">{{ $errors->first() }}</div>@endif
    <form method="post" action="{{ route('profile.update') }}" class="profile-edit-form">
        @csrf @method('PUT')
        <div><label for="profile-name">{{ __('Nombre') }}</label><input id="profile-name" name="name" value="{{ old('name', $user->name) }}" maxlength="120" required></div>
        <div><label for="profile-phone">{{ __('Teléfono (opcional)') }}</label><input id="profile-phone" name="phone" type="tel" value="{{ old('phone', $user->phone) }}" maxlength="40" autocomplete="tel"></div>
        <div class="profile-readonly"><span>{{ __('Correo electrónico') }}</span><strong>{{ $user->email }}</strong><small>{{ __('El correo de acceso no se cambia desde esta pantalla.') }}</small></div>
        <div class="form-actions"><button class="button button-primary" type="submit">{{ __('Guardar cambios') }}</button><a class="button button-ghost" href="{{ route('profile.show') }}">{{ __('Cancelar') }}</a></div>
    </form>
</section>
@endsection
