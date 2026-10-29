@extends('layout')
@section('title', 'Identidad visual · '.$company->name)
@section('page-label', 'Identidad visual')
@section('content')
<section class="panel form-panel">
    <p class="eyebrow">Configuración de empresa</p>
    <h1>Identidad visual</h1>
    <p class="intro">Estos colores y el logotipo aparecerán en el espacio de trabajo de {{ $company->name }}. Los cambios se aplican a los usuarios de esta empresa.</p>
    @if($errors->any())<div class="notice notice-error" role="alert">{{ $errors->first() }}</div>@endif
    <form method="post" action="{{ route('branding.update') }}" enctype="multipart/form-data" class="form-stack">
        @csrf @method('PUT')
        <label for="theme_primary">Color principal</label>
        <input id="theme_primary" type="color" name="theme_primary" value="{{ old('theme_primary', $company->theme_primary) }}" required>
        <label for="theme_secondary">Color del menú</label>
        <input id="theme_secondary" type="color" name="theme_secondary" value="{{ old('theme_secondary', $company->theme_secondary) }}" required>
        <label for="theme_accent">Color de acento</label>
        <input id="theme_accent" type="color" name="theme_accent" value="{{ old('theme_accent', $company->theme_accent) }}" required>
        <label for="logo">Logotipo PNG, JPG o WebP (máximo 1 MB)</label>
        <input id="logo" type="file" name="logo" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp">
        @if(in_array($company->slug, ['pepsi-demo', 'coca-cola-demo'], true))
            <label for="logo_mode">Logotipo visible</label>
            <select id="logo_mode" name="logo_mode"><option value="demo" @selected($company->logo_mode === 'demo')>De demostración</option><option value="custom" @selected($company->logo_mode !== 'demo')>Cargado por la empresa</option></select>
        @else
            <input type="hidden" name="logo_mode" value="custom">
        @endif
        @if($company->logo_path)<p class="hint">Hay un logotipo cargado. Subí otro archivo para reemplazarlo.</p>@else<p class="hint">Mientras no subas uno, se muestra una marca con la inicial de la empresa.</p>@endif
        <button class="button" type="submit">Guardar identidad visual</button>
    </form>
</section>
@endsection
