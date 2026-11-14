@extends('layout')
@section('title', $template->name.' · Multitena')
@section('content')
<section class="panel">
    <div class="section-head"><div><p class="eyebrow">{{ __('Plantilla vacía') }}</p><h1>{{ $template->name }}</h1><p class="intro">{{ __('Esta vista muestra solo los nombres y tipos de campos, nunca los valores de credenciales.') }}</p></div></div>
    <p><a class="button button-ghost" href="{{ route('credential-templates.index') }}">← {{ __('Volver a plantillas') }}</a></p>
    @if($errors->any())<div class="notice notice-error" role="alert">{{ $errors->first() }}</div>@endif
    <div class="table-scroll"><table><thead><tr><th>{{ __('Campo') }}</th><th>{{ __('Tipo') }}</th><th>{{ __('Obligatorio') }}</th></tr></thead><tbody>
        @foreach($template->fields as $field)<tr><td>{{ $field->name }}</td><td>{{ __(['text' => 'Texto', 'password' => 'Contraseña', 'url' => 'URL'][$field->type]) }}</td><td>{{ $field->required ? __('Sí') : __('No') }}</td></tr>@endforeach
    </tbody></table></div>
    @if($canShare)
        <div class="module-links"><a href="{{ route('credential-templates.sharing', $template) }}"><strong>{{ __('Compartir plantilla vacía') }}</strong><span>{{ __('Destinatarios y límites de acceso') }}</span></a></div>
    @endif

</section>
@endsection
