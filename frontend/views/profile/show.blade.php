@extends('layout')
@section('title', __('Mi perfil'))
@section('page-css', 'profile-show')
@section('content')
<section class="panel profile-page">
    <div class="section-head"><div><p class="eyebrow">{{ __('Cuenta') }} / {{ __('Preferencias') }}</p><h1>{{ __('Mi perfil') }}</h1><p class="intro">{{ __('Controla tus datos, idioma y seguridad personal.') }}</p></div></div>
    <div class="profile-identity">
        <span class="profile-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
        <div><strong>{{ $user->name }}</strong><span>{{ __(['super_admin' => 'Superadministrador', 'company_admin' => 'Administrador de empresa', 'branch_admin' => 'Administrador de sucursal', 'employee' => 'Empleado'][$user->role] ?? $user->role) }}@if($user->company) · {{ $user->company->name }}@endif</span></div>
        <a class="button button-ghost" href="{{ route('profile.edit') }}">{{ __('Editar perfil') }}</a>
    </div>
    <div class="list-heading"><div><h2>{{ __('Información personal') }}</h2></div><span>{{ __('Datos de tu cuenta') }}</span></div>
    <dl class="profile-rows">
        <div><dt>{{ __('Nombre') }}</dt><dd>{{ $user->name }}</dd></div>
        <div><dt>{{ __('Correo') }}</dt><dd>{{ $user->email }}</dd></div>
        <div><dt>{{ __('Teléfono') }}</dt><dd>{{ $user->phone ?: '—' }}</dd></div>
        @if($user->branch)<div><dt>{{ __('Sucursal') }}</dt><dd>{{ $user->branch->name }}</dd></div>@endif
        @if($user->category)<div><dt>{{ __('Categoría') }}</dt><dd>{{ $user->category->name }}</dd></div>@endif
    </dl>
    <div class="list-heading"><div><h2>{{ __('Preferencias y seguridad') }}</h2></div><span>{{ __('Estos cambios afectan solo a tu cuenta') }}</span></div>
    <div class="profile-settings-row"><strong>{{ __('Idioma') }}</strong>@include('partials.language-switcher')</div>
    @unless(session('demo_preview'))<a class="profile-settings-row" href="{{ route('password.edit') }}"><strong>{{ __('Contraseña') }}</strong><span>{{ __('Cambiar contraseña') }} →</span></a>@endunless
    @if($user->role === 'company_admin')<a class="profile-settings-row" href="{{ route('branding.edit') }}"><strong>{{ __('Identidad visual') }}</strong><span>{{ __('Colores y logotipo') }} →</span></a>@endif
</section>
@endsection
