@extends('layout')
@section('title', __('Gestión de Credenciales').' · Multitena')
@section('page-css', 'credentials-index')
@section('content')
<section class="panel vault-page">
    <div class="section-head"><div><p class="eyebrow">{{ $isPlatformInbox ? __('Plataforma') : __('Empresa') }} / {{ __('Accesos') }}</p><h1>{{ $isPlatformInbox ? __('Compartidas conmigo') : __('Gestión de Credenciales') }}</h1><p class="intro">{{ $isPlatformInbox ? __('Credenciales que otras empresas compartieron contigo.') : __('Guardá, encontrá y compartí accesos desde un solo lugar.') }}</p></div>
        @can('create', \App\Models\Credential::class)<a class="button button-primary" href="{{ route('credentials.create') }}">{{ __('Nueva credencial') }}</a>@endcan
    </div>
    <div class="vault-layout">
    <nav class="vault-nav" aria-label="{{ __('Secciones de credenciales') }}">
        <p>{{ __('Biblioteca') }}</p>
    @unless($isPlatformInbox)
        @foreach(['mine' => 'Mis credenciales', 'shared' => 'Compartidas conmigo', 'favorites' => 'Favoritas'] as $key => $label)
            <a href="{{ route('credentials.index', ['section' => $key]) }}" @if($section === $key) aria-current="page" @endif><span>{{ __($label) }}</span><span aria-hidden="true">→</span></a>
        @endforeach
    @else
        <a href="{{ route('credentials.index') }}" aria-current="page"><span>{{ __('Compartidas conmigo') }}</span><span aria-hidden="true">→</span></a>
    @endunless
        <p>{{ __('Herramientas') }}</p>
        @unless($isPlatformInbox)<a href="{{ route('credential-templates.index') }}"><span>{{ __('Plantillas') }}</span><span aria-hidden="true">→</span></a>@endunless
    </nav>
    <div class="vault-content">
    <div class="section-heading"><div><p class="eyebrow">{{ __('Biblioteca') }}</p><h2>{{ $isPlatformInbox ? __('Compartidas conmigo') : __(['mine' => 'Mis credenciales', 'shared' => 'Compartidas conmigo', 'favorites' => 'Favoritas'][$section] ?? 'Mis credenciales') }}</h2></div><span class="section-caption">{{ $credentials->total() }} {{ $credentials->total() === 1 ? __('registro') : __('registros') }}</span></div>
    <form class="filter-bar" method="get" action="{{ route('credentials.index') }}">
        @unless($isPlatformInbox)<input type="hidden" name="section" value="{{ $section }}">@endunless
        <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Buscar por nombre') }}" aria-label="{{ __('Buscar por nombre') }}">
        @if($isPlatformInbox)<select name="company" aria-label="{{ __('Empresa de origen') }}"><option value="">{{ __('Todas las empresas') }}</option>@foreach($companies as $company)<option value="{{ $company->id }}" @selected(request('company') == $company->id)>{{ $company->name }}</option>@endforeach</select>@endif
        <select name="template" aria-label="{{ __('Plantilla') }}"><option value="">{{ __('Todas las plantillas') }}</option>@foreach($templates as $item)<option value="{{ $item->id }}" @selected(request('template') == $item->id)>{{ $item->name }}</option>@endforeach</select>
        @unless($isPlatformInbox)<select name="visibility" aria-label="{{ __('Modalidad') }}"><option value="">{{ __('Todas') }}</option><option value="private" @selected(request('visibility') === 'private')>{{ __('Privada') }}</option><option value="shared" @selected(request('visibility') === 'shared')>{{ __('Compartida') }}</option></select>@endunless
        <button class="button button-ghost" type="submit">{{ __('Filtrar') }}</button>
    </form>
    @if($credentials->isEmpty())<p class="empty-state">{{ __('No hay credenciales disponibles con estos filtros.') }}</p>@else
    <div class="vault-list">
    @foreach($credentials as $item)
        <article class="vault-item">
            <span class="vault-item-icon" aria-hidden="true"><svg class="icon"><use href="#icon-key"/></svg></span>
            <div class="vault-item-info"><strong>{{ $item->title }}</strong><span>{{ $item->template->name }} · {{ $isPlatformInbox ? $item->company->name : ($item->visibility === 'private' ? __('Privada') : __('Compartida')) }} · {{ $item->owner->name }}</span></div>
            <div class="credential-row-actions"><a class="button button-ghost button-sm" href="{{ route('credentials.show', $item) }}">{{ __('Ver detalle') }} <span aria-hidden="true">→</span></a>@unless($isPlatformInbox)<form method="post" action="{{ $item->is_favorite ? route('credentials.favorite.destroy', $item) : route('credentials.favorite.store', $item) }}">@csrf @if($item->is_favorite) @method('DELETE') @endif<button class="button button-ghost button-sm" type="submit" aria-label="{{ $item->is_favorite ? __('Quitar de favoritas') : __('Agregar a favoritas') }}" title="{{ $item->is_favorite ? __('Quitar de favoritas') : __('Agregar a favoritas') }}">{{ $item->is_favorite ? '★' : '☆' }}</button></form>@endunless</div>
        </article>
    @endforeach
    </div>@endif
    {{ $credentials->links('pagination.simple') }}
    </div>
    </div>
</section>
@endsection
