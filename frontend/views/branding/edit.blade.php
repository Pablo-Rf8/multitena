@extends('layout')
@section('title', __('Identidad visual').' · '.$company->name)
@section('page-label', __('Identidad visual'))
@section('page-css', 'branding-edit')
@section('page-js', 'branding-edit')
@section('content')
<section class="branding-page">
    <div class="section-head">
        <div><p class="eyebrow">{{ __('Empresa') }} / {{ __('Preferencias') }}</p><h1>{{ __('Identidad visual') }}</h1><p class="intro">{{ __('Personaliza la marca que verá tu equipo.') }}</p></div>
    </div>
    @if($errors->any())<div class="notice notice-error" role="alert">{{ $errors->first() }}</div>@endif
    <form method="post" action="{{ route('branding.update') }}" enctype="multipart/form-data" class="branding-grid" data-branding-form>
        @csrf @method('PUT')
        <div class="branding-card">
            <h2>{{ __('Logotipo de la empresa') }}</h2>
            <p class="muted">{{ __('Carga una imagen para reemplazar el logotipo actual cuando quieras.') }}</p>
            <div class="branding-upload">
                <strong data-file-label>{{ $company->logo_path ? __('Logotipo propio cargado') : __('Sin logotipo propio') }}</strong>
                <span>{{ __('PNG, JPG o WebP · hasta 1 MB') }}</span>
                <label for="logo" class="sr-only">{{ __('Elegir imagen') }}</label>
                <input id="logo" type="file" name="logo" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp" aria-describedby="logo-help">
            </div>
            <p id="logo-help" class="hint">
                @if($company->logo_path)
                    {{ __('Al subir otra imagen, reemplazarás el logotipo visible. El archivo anterior se conserva.') }}
                @elseif(in_array($company->slug, ['pepsi-demo', 'coca-cola-demo'], true))
                    {{ __('Se muestra el logotipo DEMO hasta que subas uno propio.') }}
                @else
                    {{ __('Mientras no subas un logotipo, se muestra solo el nombre de la empresa.') }}
                @endif
            </p>
            @if(in_array($company->slug, ['pepsi-demo', 'coca-cola-demo'], true))
                <label class="branding-mode-label" for="logo_mode">{{ __('Logotipo visible') }}</label>
                <select id="logo_mode" name="logo_mode"><option value="demo" @selected($company->logo_mode === 'demo')>{{ __('De demostración') }}</option><option value="custom" @selected($company->logo_mode !== 'demo')>{{ __('Cargado por la empresa') }}</option></select>
            @else
                <input type="hidden" name="logo_mode" value="custom">
            @endif
            <h2 class="branding-colors-title">{{ __('Colores de la empresa') }}</h2>
            <div class="branding-colors">
                <label><span>{{ __('Principal') }}</span><input type="color" name="theme_primary" value="{{ old('theme_primary', $company->theme_primary) }}" required><small data-color-value>{{ old('theme_primary', $company->theme_primary) }}</small></label>
                <label><span>{{ __('Menú') }}</span><input type="color" name="theme_secondary" value="{{ old('theme_secondary', $company->theme_secondary) }}" required><small data-color-value>{{ old('theme_secondary', $company->theme_secondary) }}</small></label>
                <label><span>{{ __('Acento') }}</span><input type="color" name="theme_accent" value="{{ old('theme_accent', $company->theme_accent) }}" required><small data-color-value>{{ old('theme_accent', $company->theme_accent) }}</small></label>
            </div>
            <button class="button button-primary" type="submit">{{ __('Guardar identidad visual') }}</button>
        </div>
        <div class="branding-preview-card">
            <p class="eyebrow">{{ __('Vista previa') }}</p>
            <h2>{{ __('Así se verá en el sistema') }}</h2>
            <div class="branding-preview">
                <div class="branding-preview-heading">
                    @if($company->logoUrl())<img src="{{ $company->logoUrl() }}" alt="" data-logo-preview>@else<span class="branding-preview-placeholder" data-logo-placeholder>{{ __('Logo') }}</span><img alt="" data-logo-preview hidden>@endif
                    <span><strong>{{ $company->name }}</strong><small>{{ __('Espacio de empresa') }}</small></span>
                </div>
                <div class="branding-preview-link active">{{ __('Inicio') }}</div>
                <div class="branding-preview-link">{{ __('Contactos') }}</div>
                <div class="branding-preview-link">{{ __('Credenciales') }}</div>
                <div class="branding-preview-link">{{ __('Personal') }}</div>
            </div>
            <p class="hint">{{ __('Al elegir una imagen, aparecerá aquí y en el acceso de tu empresa después de guardar.') }}</p>
        </div>
    </form>
</section>
@endsection
