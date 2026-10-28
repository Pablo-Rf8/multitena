@extends('layout')

@section('title', $contact->name.' · Multitena')

@section('content')
<section class="panel">
    <a class="back-link" href="{{ route('contacts.index') }}">← Volver a contactos</a>
    <div class="section-head">
        <div>
            <p class="eyebrow">Ficha de contacto</p>
            <h1>{{ $contact->name }} @if($contact->is_demo)<span class="tag">DEMO</span>@endif</h1>
            <p class="intro">{{ $contact->organization ?: 'Sin organización' }} · {{ $contact->branch->name }}</p>
        </div>
        <a class="button button-secondary" href="{{ route('contacts.edit', $contact) }}">Editar contacto</a>
    </div>
    <dl class="details">
        <div><dt>Estado</dt><dd>{{ ['prospect'=>'Prospecto','qualified'=>'Calificado','customer'=>'Cliente','inactive'=>'Inactivo'][$contact->status] ?? $contact->status }}</dd></div>
        <div><dt>Correo</dt><dd>{{ $contact->email ?: 'Sin registrar' }}</dd></div>
        <div><dt>Teléfono</dt><dd>{{ $contact->phone ?: 'Sin registrar' }}</dd></div>
        <div><dt>Origen</dt><dd>{{ $contact->source ?: 'Sin registrar' }}</dd></div>
    </dl>
    <div class="section-head section-subhead">
        <h2>Oportunidades</h2>
        <a class="button button-secondary" href="{{ route('opportunities.create', ['contact' => $contact->id]) }}">Agregar oportunidad</a>
    </div>
    @if($contact->opportunities->isEmpty())
        <p class="empty-state">Este contacto todavía no tiene oportunidades.</p>
    @else
        <div class="table-scroll">
            <table>
                <thead><tr><th>Oportunidad</th><th>Etapa</th><th>Monto</th><th>Cierre estimado</th><th>Acción</th></tr></thead>
                <tbody>
                    @foreach($contact->opportunities as $item)
                        <tr>
                            <td>{{ $item->title }} @if($item->is_demo)<span class="tag">DEMO</span>@endif</td>
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
    <h2>Seguimiento</h2>
    <form method="post" action="{{ route('contacts.notes.store', $contact) }}" class="form-stack note-form">
        @csrf
        <label for="body">Nueva nota</label>
        <textarea id="body" name="body" rows="4" maxlength="5000" required placeholder="Registrá una llamada, reunión o próximo paso">{{ old('body') }}</textarea>
        @error('body')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        <button class="button" type="submit">Agregar nota</button>
    </form>
    @forelse($contact->notes as $note)
        <article class="note"><div class="muted">{{ $note->author->name }} · {{ $note->created_at->format('d/m/Y H:i') }}</div><p>{{ $note->body }}</p></article>
    @empty
        <p class="empty-state">Todavía no hay notas de seguimiento.</p>
    @endforelse
</section>
@endsection
