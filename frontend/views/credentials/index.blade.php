@extends('layout')
@section('title', __('Gestión de Credenciales').' · Multitena')
@section('content')
<section class="panel">
    <div class="section-head"><div><p class="eyebrow">{{ __('Empresa') }}</p><h1>{{ __('Gestión de Credenciales') }}</h1><p class="intro">{{ __('Accesos de tu empresa compartidos con permisos individuales.') }}</p></div>
        @can('create', \App\Models\Credential::class)<a class="button button-primary" href="{{ route('credentials.create') }}">{{ __('Nueva credencial') }}</a>@endcan
    </div>
    @can('viewAny', \App\Models\CredentialTemplate::class)<p><a class="button button-ghost" href="{{ route('credential-templates.index') }}">{{ __('Administrar plantillas') }}</a></p>@endcan
    <form class="filter-bar" method="get" action="{{ route('credentials.index') }}">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Buscar por nombre') }}" aria-label="{{ __('Buscar por nombre') }}">
        <select name="template" aria-label="{{ __('Plantilla') }}"><option value="">{{ __('Todas las plantillas') }}</option>@foreach($templates as $item)<option value="{{ $item->id }}" @selected(request('template') == $item->id)>{{ $item->name }}</option>@endforeach</select>
        <select name="visibility" aria-label="{{ __('Modalidad') }}"><option value="">{{ __('Todas') }}</option><option value="private" @selected(request('visibility') === 'private')>{{ __('Privada') }}</option><option value="shared" @selected(request('visibility') === 'shared')>{{ __('Compartida') }}</option></select>
        <button class="button button-ghost" type="submit">{{ __('Filtrar') }}</button>
    </form>
    @if($credentials->isEmpty())<p class="empty-state">{{ __('No hay credenciales disponibles con estos filtros.') }}</p>@else
    <div class="table-scroll"><table><thead><tr><th>{{ __('Credencial') }}</th><th>{{ __('Plantilla') }}</th><th>{{ __('Modalidad') }}</th><th>{{ __('Propietario') }}</th><th>{{ __('Acción') }}</th></tr></thead><tbody>
    @foreach($credentials as $item)<tr><td><strong>{{ $item->title }}</strong></td><td>{{ $item->template->name }}</td><td>{{ $item->visibility === 'private' ? __('Privada') : __('Compartida') }}</td><td>{{ $item->owner->name }}</td><td><a class="button button-ghost button-sm" href="{{ route('credentials.show', $item) }}">{{ __('Ver detalle') }}</a></td></tr>@endforeach
    </tbody></table></div>@endif
    {{ $credentials->links('pagination.simple') }}
</section>
@endsection
