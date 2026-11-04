@extends('layout')

@section('title', __('Contactos').' · Multitena')

@section('content')
<section class="panel">
    <div class="section-head">
        <div>
            <p class="eyebrow">CRM</p>
            <h1>{{ __('Contactos') }}</h1>
            <p class="intro">{{ __('Personas y organizaciones que atiende tu equipo.') }}</p>
        </div>
        @if(auth()->user()->role !== 'employee' || in_array('contacts.write', auth()->user()->category?->permissions ?? [], true))<a class="button button-primary" href="{{ route('contacts.create') }}"><svg class="icon" aria-hidden="true"><use href="#icon-plus"/></svg>{{ __('Agregar contacto') }}</a>@endif
    </div>
    <form method="get" action="{{ route('contacts.index') }}" class="filter-bar">
        <label class="sr-only" for="contact-search">{{ __('Buscar') }}</label>
        <input id="contact-search" name="q" type="search" value="{{ request('q') }}" placeholder="{{ __('Nombre, organización o correo') }}">
        <label class="sr-only" for="contact-status">{{ __('Estado') }}</label>
        <select id="contact-status" name="status">
            <option value="">{{ __('Todos los estados') }}</option>
            @foreach(['prospect' => 'Prospecto', 'qualified' => 'Calificado', 'customer' => 'Cliente', 'inactive' => 'Inactivo'] as $key => $label)
                <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        @if(auth()->user()->role === 'company_admin')
            <label class="sr-only" for="contact-branch">{{ __('Sucursal') }}</label>
            <select id="contact-branch" name="branch_id">
                <option value="">{{ __('Todas las sucursales') }}</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" @selected(request('branch_id') == $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
        @endif
        <button class="button button-ghost" type="submit">{{ __('Filtrar') }}</button>
        <a class="button button-ghost" href="{{ route('contacts.export', request()->only(['q','status','branch_id'])) }}">{{ __('Exportar CSV') }}</a>
    </form>
    @if($contacts->isEmpty())
        <p class="empty-state">{{ __('No hay contactos con estos filtros.') }}</p>
    @else
        <div class="table-scroll">
            <table>
                <thead><tr><th>{{ __('Contacto') }}</th><th>{{ __('Organización') }}</th><th>{{ __('Estado') }}</th><th>{{ __('Sucursal') }}</th><th>{{ __('Correo') }}</th><th>{{ __('Acción') }}</th></tr></thead>
                <tbody>
                    @foreach($contacts as $contact)
                        <tr>
                            <td><strong>{{ $contact->name }}</strong> @if($contact->is_demo)<span class="tag">DEMO</span>@endif</td>
                            <td>{{ $contact->organization ?: '—' }}</td>
                            <td>{{ ['prospect'=>'Prospecto','qualified'=>'Calificado','customer'=>'Cliente','inactive'=>'Inactivo'][$contact->status] ?? $contact->status }}</td>
                            <td>{{ $contact->branch->name }}</td>
                            <td>{{ $contact->email ?: '—' }}</td>
                            <td><a class="button button-ghost button-sm" href="{{ route('contacts.show', $contact) }}">{{ __('Ver ficha') }}</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
    {{ $contacts->links('pagination.simple') }}
</section>
@endsection
