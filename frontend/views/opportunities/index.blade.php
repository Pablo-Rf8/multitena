@extends('layout')

@section('title', 'Oportunidades · Multitena')

@section('content')
<section class="panel">
    <div class="section-head">
        <div>
            <p class="eyebrow">CRM</p>
            <h1>Oportunidades</h1>
            <p class="intro">Seguimiento de posibles ventas, desde el primer contacto hasta el cierre.</p>
        </div>
        <a class="button" href="{{ route('contacts.index') }}">Elegir contacto</a>
    </div>
    <div class="stats">
        @foreach(['new'=>'Nuevas','qualified'=>'Calificadas','proposal'=>'Propuestas','won'=>'Ganadas','lost'=>'Perdidas'] as $key => $label)
            <div class="stat"><strong>{{ $totals[$key]->total ?? 0 }}</strong><span>{{ $label }}</span></div>
        @endforeach
    </div>
    <form method="get" action="{{ route('opportunities.index') }}" class="filter-bar">
        <label class="sr-only" for="opportunity-search">Buscar</label>
        <input id="opportunity-search" name="q" type="search" value="{{ request('q') }}" placeholder="Buscar oportunidad">
        <label class="sr-only" for="opportunity-stage">Etapa</label>
        <select id="opportunity-stage" name="stage">
            <option value="">Todas las etapas</option>
            @foreach(['new'=>'Nueva','qualified'=>'Calificada','proposal'=>'Propuesta','won'=>'Ganada','lost'=>'Perdida'] as $key => $label)
                <option value="{{ $key }}" @selected(request('stage') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        @if(auth()->user()->role === 'company_admin')
            <label class="sr-only" for="opportunity-branch">Sucursal</label>
            <select id="opportunity-branch" name="branch_id">
                <option value="">Todas las sucursales</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" @selected(request('branch_id') == $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
        @endif
        <button class="button button-secondary" type="submit">Filtrar</button>
        <a href="{{ route('opportunities.export', request()->only(['q','stage','branch_id'])) }}">Exportar CSV</a>
    </form>
    @if($opportunities->isEmpty())
        <p class="empty-state">No hay oportunidades con estos filtros. Abrí un contacto para crear una.</p>
    @else
        <div class="table-scroll">
            <table>
                <thead><tr><th>Oportunidad</th><th>Contacto</th><th>Sucursal</th><th>Etapa</th><th>Monto</th><th>Cierre</th><th>Acción</th></tr></thead>
                <tbody>
                    @foreach($opportunities as $item)
                        <tr>
                            <td><strong>{{ $item->title }}</strong> @if($item->is_demo)<span class="tag">DEMO</span>@endif</td>
                            <td><a href="{{ route('contacts.show', $item->contact) }}">{{ $item->contact->name }}</a></td>
                            <td>{{ $item->branch->name }}</td>
                            <td>{{ ['new'=>'Nueva','qualified'=>'Calificada','proposal'=>'Propuesta','won'=>'Ganada','lost'=>'Perdida'][$item->stage] ?? $item->stage }}</td>
                            <td>{{ number_format($item->amount_cents / 100, 2) }} {{ auth()->user()->company->currency }}</td>
                            <td>{{ $item->expected_close_on?->format('d/m/Y') ?? '—' }}</td>
                            <td><a href="{{ route('opportunities.edit', $item) }}">Editar</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
    {{ $opportunities->links('pagination.simple') }}
</section>
@endsection
