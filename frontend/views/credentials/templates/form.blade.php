@extends('layout')
@section('title', ($template ? __('Editar plantilla') : __('Nueva plantilla')).' · Multitena')
@section('page-js', 'credential-templates-form')
@section('page-css', 'credential-templates-form')
@section('content')
<section class="panel"><div class="section-head"><div><p class="eyebrow">{{ __('Gestión de Credenciales') }}</p><h1>{{ $template ? __('Editar plantilla') : __('Nueva plantilla') }}</h1><p class="intro">{{ __('Agregá, ordená y marcá los campos obligatorios.') }}</p></div></div>
@if($errors->any())<div class="notice notice-error" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@php($formFields = old('fields', $template?->fields?->toArray() ?? [['name' => '', 'type' => 'text', 'required' => false]]))
<form method="post" action="{{ $template ? route('credential-templates.update', $template) : route('credential-templates.store') }}" class="form-stack credential-form">@csrf @if($template) @method('PUT') @endif
<label for="template-name">{{ __('Nombre de la plantilla') }}</label><input id="template-name" name="name" maxlength="120" value="{{ old('name', $template?->name) }}" required>
<h2>{{ __('Campos') }}</h2><div id="template-fields" data-next-index="{{ $formFields ? max(array_map('intval', array_keys($formFields))) + 1 : 0 }}" data-label-name="{{ __('Nombre') }}" data-label-type="{{ __('Tipo') }}" data-label-required="{{ __('Obligatorio') }}" data-label-remove="{{ __('Quitar') }}" data-label-up="{{ __('Subir campo') }}" data-label-down="{{ __('Bajar campo') }}" data-label-text="{{ __('Texto') }}" data-label-password="{{ __('Contraseña') }}" data-label-url="URL">
@foreach($formFields as $i => $field)
<div class="credential-field-row" data-field-row>
@if(isset($field['id']))<input type="hidden" name="fields[{{ $i }}][id]" value="{{ $field['id'] }}">@endif
<label>{{ __('Nombre') }} <input name="fields[{{ $i }}][name]" value="{{ $field['name'] ?? '' }}" maxlength="120" required></label>
<label>{{ __('Tipo') }} <select name="fields[{{ $i }}][type]">@foreach(['text'=>__('Texto'),'password'=>__('Contraseña'),'url'=>'URL'] as $type => $label)<option value="{{ $type }}" @selected(($field['type'] ?? 'text') === $type)>{{ $label }}</option>@endforeach</select></label>
<label class="credential-check"><input type="checkbox" name="fields[{{ $i }}][required]" value="1" @checked($field['required'] ?? false)> {{ __('Obligatorio') }}</label>
<div class="credential-row-actions"><button type="button" class="button button-ghost button-sm" data-field-up aria-label="{{ __('Subir campo') }}">↑</button><button type="button" class="button button-ghost button-sm" data-field-down aria-label="{{ __('Bajar campo') }}">↓</button><button type="button" class="button button-ghost button-sm" data-field-remove>{{ __('Quitar') }}</button></div>
</div>@endforeach</div>
<button type="button" class="button button-ghost" id="add-template-field">+ {{ __('Agregar campo') }}</button>
<div class="form-actions"><button class="button button-primary" type="submit">{{ __('Guardar plantilla') }}</button><a class="button button-ghost" href="{{ route('credential-templates.index') }}">{{ __('Cancelar') }}</a></div>
</form>
@if($template && $template->credentials_count === 0)<form method="post" action="{{ route('credential-templates.destroy', $template) }}">@csrf @method('DELETE')<button class="button button-ghost" type="submit" data-confirm="{{ __('¿Eliminar esta plantilla?') }}">{{ __('Eliminar plantilla') }}</button></form>@elseif($template)<p class="hint">{{ __('Solo puede eliminarse si no tiene credenciales.') }}</p>@endif
</section>
@endsection
