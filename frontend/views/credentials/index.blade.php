@extends('layout')
@section('title', __('Gestión de Credenciales').' · Multitena')
@section('content')
<section class="panel">
    <div class="section-head"><div><p class="eyebrow">{{ $isPlatformInbox ? __('Plataforma') : __('Empresa') }}</p><h1>{{ $isPlatformInbox ? __('Compartidas conmigo') : __('Gestión de Credenciales') }}</h1><p class="intro">{{ $isPlatformInbox ? __('Credenciales compartidas por empresas contigo. Solo podés abrir las que sigan autorizadas.') : __('Accesos de tu empresa compartidos con permisos individuales.') }}</p></div>
        @can('create', \App\Models\Credential::class)<a class="button button-primary" href="{{ route('credentials.create') }}">{{ __('Nueva credencial') }}</a>@endcan
    </div>
    @unless($isPlatformInbox)<nav class="credential-tabs" aria-label="{{ __('Secciones de credenciales') }}">
        @foreach(['mine' => 'Mis credenciales', 'shared' => 'Compartidas conmigo', 'favorites' => 'Favoritas'] as $key => $label)
            <a class="button button-ghost {{ $section === $key ? 'credential-tab-active' : '' }}" href="{{ route('credentials.index', ['section' => $key]) }}" @if($section === $key) aria-current="page" @endif>{{ __($label) }}</a>
        @endforeach
    </nav>@endunless
    <form class="filter-bar" method="get" action="{{ route('credentials.index') }}">
        @unless($isPlatformInbox)<input type="hidden" name="section" value="{{ $section }}">@endunless
        <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Buscar por nombre') }}" aria-label="{{ __('Buscar por nombre') }}">
        @if($isPlatformInbox)<select name="company" aria-label="{{ __('Empresa de origen') }}"><option value="">{{ __('Todas las empresas') }}</option>@foreach($companies as $company)<option value="{{ $company->id }}" @selected(request('company') == $company->id)>{{ $company->name }}</option>@endforeach</select>@endif
        <select name="template" aria-label="{{ __('Plantilla') }}"><option value="">{{ __('Todas las plantillas') }}</option>@foreach($templates as $item)<option value="{{ $item->id }}" @selected(request('template') == $item->id)>{{ $item->name }}</option>@endforeach</select>
        @unless($isPlatformInbox)<select name="visibility" aria-label="{{ __('Modalidad') }}"><option value="">{{ __('Todas') }}</option><option value="private" @selected(request('visibility') === 'private')>{{ __('Privada') }}</option><option value="shared" @selected(request('visibility') === 'shared')>{{ __('Compartida') }}</option></select>@endunless
        <button class="button button-ghost" type="submit">{{ __('Filtrar') }}</button>
    </form>
    @if($credentials->isEmpty())<p class="empty-state">{{ __('No hay credenciales disponibles con estos filtros.') }}</p>@else
    <div class="table-scroll"><table><thead><tr><th>{{ __('Credencial') }}</th>@if($isPlatformInbox)<th>{{ __('Empresa de origen') }}</th>@endif<th>{{ __('Plantilla') }}</th>@unless($isPlatformInbox)<th>{{ __('Modalidad') }}</th>@endunless<th>{{ __('Propietario') }}</th><th>{{ __('Acción') }}</th></tr></thead><tbody>
    @foreach($credentials as $item)<tr><td><strong>{{ $item->title }}</strong></td>@if($isPlatformInbox)<td>{{ $item->company->name }}</td>@endif<td>{{ $item->template->name }}</td>@unless($isPlatformInbox)<td>{{ $item->visibility === 'private' ? __('Privada') : __('Compartida') }}</td>@endunless<td>{{ $item->owner->name }}</td><td><div class="credential-row-actions"><a class="button button-ghost button-sm" href="{{ route('credentials.show', $item) }}">{{ __('Ver detalle') }}</a>@unless($isPlatformInbox)<form method="post" action="{{ $item->is_favorite ? route('credentials.favorite.destroy', $item) : route('credentials.favorite.store', $item) }}">@csrf @if($item->is_favorite) @method('DELETE') @endif<button class="button button-ghost button-sm" type="submit" aria-label="{{ $item->is_favorite ? __('Quitar de favoritas') : __('Agregar a favoritas') }}" title="{{ $item->is_favorite ? __('Quitar de favoritas') : __('Agregar a favoritas') }}">{{ $item->is_favorite ? '★' : '☆' }}</button></form>@endunless</div></td></tr>@endforeach
    </tbody></table></div>@endif
    {{ $credentials->links('pagination.simple') }}
</section>
@endsection
