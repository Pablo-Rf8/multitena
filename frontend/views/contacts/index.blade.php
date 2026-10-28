@extends('layout')

@section('title', 'Contactos · Multitena')

@section('content')
<section class="panel">
    <div class="section-head">
        <div>
            <p class="eyebrow">CRM</p>
            <h1>Contactos</h1>
            <p class="intro">Personas y organizaciones que atiende tu equipo.</p>
        </div>
        <a class="button" href="{{ route('contacts.create') }}">Agregar contacto</a>
    </div>
    <form method="get" action="{{ route('contacts.index') }}" class="filter-bar">
        <label class="sr-only" for="contact-search">Buscar</label>
        <input id="contact-search" name="q" type="search" value="{{ request('q') }}" placeholder="Nombre, organización o correo">
        <label class="sr-only" for="contact-status">Estado</label>
        <select id="contact-status" name="status">
            <option value="">Todos los estados</option>
            @foreach(['prospect' => 'Prospecto', 'qualified' => 'Calificado', 'customer' => 'Cliente', 'inactive' => 'Inactivo'] as $key => $label)
                <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        @if(auth()->user()->role === 'company_admin')
            <label class="sr-only" for="contact-branch">Sucursal</label>
            <select id="contact-branch" name="branch_id">
                <option value="">Todas las sucursales</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" @selected(request('branch_id') == $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
        @endif
        <button class="button button-secondary" type="submit">Filtrar</button>
        <a href="{{ route('contacts.export', request()->only(['q','status','branch_id'])) }}">Exportar CSV</a>
    </form>
    @if($contacts->isEmpty())
        <p class="empty-state">No hay contactos con estos filtros. Agregá uno para comenzar.</p>
    @else
        <div class="table-scroll">
            <table>
                <thead><tr><th>Contacto</th><th>Organización</th><th>Estado</th><th>Sucursal</th><th>Correo</th><th>Acción</th></tr></thead>
                <tbody>
                    @foreach($contacts as $contact)
                        <tr>
                            <td><strong>{{ $contact->name }}</strong> @if($contact->is_demo)<span class="tag">DEMO</span>@endif</td>
                            <td>{{ $contact->organization ?: '—' }}</td>
                            <td>{{ ['prospect'=>'Prospecto','qualified'=>'Calificado','customer'=>'Cliente','inactive'=>'Inactivo'][$contact->status] ?? $contact->status }}</td>
                            <td>{{ $contact->branch->name }}</td>
                            <td>{{ $contact->email ?: '—' }}</td>
                            <td><a href="{{ route('contacts.show', $contact) }}">Ver ficha</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
    {{ $contacts->links('pagination.simple') }}
</section>
@endsection
