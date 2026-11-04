@extends('layout')
@section('title', __('Plantillas de credenciales').' · Multitena')
@section('content')
<section class="panel"><div class="section-head"><div><p class="eyebrow">{{ __('Gestión de Credenciales') }}</p><h1>{{ __('Plantillas') }}</h1><p class="intro">{{ __('Definí los campos que se reutilizan al guardar credenciales.') }}</p></div>@can('create', \App\Models\CredentialTemplate::class)<a class="button button-primary" href="{{ route('credential-templates.create') }}">{{ __('Nueva plantilla') }}</a>@endcan</div>
<p><a class="button button-ghost" href="{{ route('credentials.index') }}">← {{ __('Volver a credenciales') }}</a></p>
@if($errors->any())<div class="notice notice-error" role="alert">{{ $errors->first() }}</div>@endif
@if($templates->isEmpty())<p class="empty-state">{{ __('Todavía no hay plantillas.') }}</p>@else<div class="table-scroll"><table><thead><tr><th>{{ __('Plantilla') }}</th><th>{{ __('Campos') }}</th><th>{{ __('En uso') }}</th><th>{{ __('Acción') }}</th></tr></thead><tbody>
@foreach($templates as $item)<tr><td><strong>{{ $item->name }}</strong></td><td>{{ $item->fields->pluck('name')->join(', ') }}</td><td>{{ $item->credentials_count }}</td><td>@can('update', $item)<a class="button button-ghost button-sm" href="{{ route('credential-templates.edit', $item) }}">{{ __('Editar') }}</a>@endcan</td></tr>@endforeach
</tbody></table></div>@endif</section>
@endsection
