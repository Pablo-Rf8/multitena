@extends('layout')

@section('title', __('Oportunidades').' · Multitena')

@section('content')
<section class="panel">
    <div class="section-head">
        <div>
            <p class="eyebrow">CRM</p>
            <h1>{{ __('Oportunidades') }}</h1>
            <p class="intro">{{ __('Seguimiento de posibles ventas, desde el primer contacto hasta el cierre.') }}</p>
        </div>
        @if(auth()->user()->role !== 'employee' || in_array('contacts.read', auth()->user()->category?->permissions ?? [], true))<a class="button button-primary" href="{{ route('contacts.index') }}"><svg class="icon" aria-hidden="true"><use href="#icon-users"/></svg>{{ __('Elegir contacto') }}</a>@endif
    </div>
    <div class="stats">
        @foreach(['new'=>'Nuevas','qualified'=>'Calificadas','proposal'=>'Propuestas','won'=>'Ganadas','lost'=>'Perdidas'] as $key => $label)
            <div class="stat"><strong>{{ $totals[$key]->total ?? 0 }}</strong><span>{{ $label }}</span></div>
        @endforeach
    </div>
    <form method="get" action="{{ route('opportunities.index') }}" class="filter-bar">
        <label class="sr-only" for="opportunity-search">{{ __('Buscar') }}</label>
        <input id="opportunity-search" name="q" type="search" value="{{ request('q') }}" placeholder="Buscar oportunidad">
        <label class="sr-only" for="opportunity-stage">{{ __('Etapa') }}</label>
        <select id="opportunity-stage" name="stage">
            <option value="">{{ __('Todas las etapas') }}</option>
            @foreach(['new'=>__('Nueva'),'qualified'=>'Calificada','proposal'=>'Propuesta','won'=>'Ganada','lost'=>'Perdida'] as $key => $label)
                <option value="{{ $key }}" @selected(request('stage') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        @if(auth()->user()->role === 'company_admin')
            <label class="sr-only" for="opportunity-branch">{{ __('Sucursal') }}</label>
            <select id="opportunity-branch" name="branch_id">
                <option value="">{{ __('Todas las sucursales') }}</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" @selected(request('branch_id') == $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
        @endif
        <button class="button button-ghost" type="submit">{{ __('Filtrar') }}</button>
        <a class="button button-ghost" href="{{ route('opportunities.export', request()->only(['q','stage','branch_id'])) }}">{{ __('Exportar CSV') }}</a>
    </form>
    @if($opportunities->isEmpty())
        <p class="empty-state">{{ __('No hay oportunidades con estos filtros.') }}</p>
    @else
        <div class="table-scroll">
            <table>
                <thead><tr><th>{{ __('Oportunidad') }}</th><th>{{ __('Contacto') }}</th><th>{{ __('Sucursal') }}</th><th>{{ __('Etapa') }}</th><th>{{ __('Monto') }}</th><th>{{ __('Cierre') }}</th><th>{{ __('Acción') }}</th></tr></thead>
                <tbody>
                    @foreach($opportunities as $item)
                        <tr>
                            <td><strong>{{ $item->title }}</strong> @if($item->is_demo)<span class="tag">DEMO</span>@endif</td>
            <td>@if(auth()->user()->role !== 'employee' || in_array('contacts.read', auth()->user()->category?->permissions ?? [], true))<a href="{{ route('contacts.show', $item->contact) }}">{{ $item->contact->name }}</a>@else{{ $item->contact->name }}@endif</td>
                            <td>{{ $item->branch->name }}</td>
                            <td>{{ ['new'=>__('Nueva'),'qualified'=>'Calificada','proposal'=>'Propuesta','won'=>'Ganada','lost'=>'Perdida'][$item->stage] ?? $item->stage }}</td>
                            <td>{{ number_format($item->amount_cents / 100, 2) }} {{ auth()->user()->company->currency }}</td>
                            <td>{{ $item->expected_close_on?->format('d/m/Y') ?? '—' }}</td>
            <td>@if(auth()->user()->role !== 'employee' || in_array('opportunities.write', auth()->user()->category?->permissions ?? [], true))<a class="button button-ghost button-sm" href="{{ route('opportunities.edit', $item) }}">{{ __('Editar') }}</a>@else<span class="muted">{{ __('Solo lectura') }}</span>@endif</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
    {{ $opportunities->links('pagination.simple') }}
</section>
@endsection
