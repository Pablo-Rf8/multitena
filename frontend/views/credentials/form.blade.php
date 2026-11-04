@extends('layout')
@section('title', ($credential ? __('Editar credencial') : __('Nueva credencial')).' · Multitena')
@section('content')
<section class="panel"><div class="section-head"><div><p class="eyebrow">{{ __('Gestión de Credenciales') }}</p><h1>{{ $credential ? __('Editar credencial') : __('Nueva credencial') }}</h1></div></div>
@if($errors->any())<div class="notice notice-error" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@unless($credential)<form method="get" action="{{ route('credentials.create') }}" class="filter-bar"><label for="select-template">{{ __('Plantilla') }}</label><select name="template" id="select-template" required><option value="">{{ __('Elegir plantilla') }}</option>@foreach($templates as $item)<option value="{{ $item->id }}" @selected($template?->id === $item->id)>{{ $item->name }}</option>@endforeach</select><button class="button button-ghost" type="submit">{{ __('Usar plantilla') }}</button></form>@endunless
@if($template)
<form method="post" action="{{ $credential ? route('credentials.update', $credential) : route('credentials.store') }}" class="form-stack credential-form">@csrf @if($credential) @method('PUT') @else <input type="hidden" name="template_id" value="{{ $template->id }}"> @endif
<p class="muted">{{ __('Plantilla') }}: {{ $template->name }}</p>
<label for="credential-title">{{ __('Nombre de la credencial') }}</label><input id="credential-title" name="title" maxlength="160" value="{{ old('title', $credential?->title) }}" required>
@foreach($template->fields as $field)
<label for="credential-field-{{ $field->id }}">{{ $field->name }} @if($field->required)<span aria-hidden="true">*</span>@endif</label>
<input id="credential-field-{{ $field->id }}" name="values[{{ $field->id }}]" type="{{ $field->type === 'password' ? 'password' : ($field->type === 'url' ? 'url' : 'text') }}" @if($field->type !== 'password') value="{{ old('values.'.$field->id, $values->get($field->id)?->value) }}" @endif @if($field->required && !($field->type === 'password' && $values->get($field->id)?->secret_ciphertext)) required @endif maxlength="2048" autocomplete="off" @if($credential && $field->type === 'password') placeholder="{{ __('Dejar vacío para conservar') }}" @endif>
@endforeach
<label for="credential-visibility">{{ __('Modalidad') }}</label>
@if($credential && $credential->owner_id !== auth()->id())<input type="hidden" name="visibility" value="{{ $credential->visibility }}"><p>{{ $credential->visibility === 'private' ? __('Privada') : __('Compartida') }}</p>@else
<select id="credential-visibility" name="visibility"><option value="private" @selected(old('visibility', $credential?->visibility ?? 'private') === 'private')>{{ __('Privada') }}</option><option value="shared" @selected(old('visibility', $credential?->visibility) === 'shared')>{{ __('Compartida con la empresa') }}</option></select>
<p class="hint">{{ __('El acceso se concede a cada persona por separado. Compartida permite incluir empleados; privada solo administradores autorizados.') }}</p>@endif
<div class="form-actions"><button class="button button-primary" type="submit">{{ __('Guardar credencial') }}</button><a class="button button-ghost" href="{{ $credential ? route('credentials.show', $credential) : route('credentials.index') }}">{{ __('Cancelar') }}</a></div>
</form>
@elseif($templates->isEmpty())<p class="empty-state">{{ __('Primero creá una plantilla.') }} @can('create', \App\Models\CredentialTemplate::class)<a href="{{ route('credential-templates.create') }}">{{ __('Nueva plantilla') }}</a>@endcan</p>@endif
</section>
@endsection
