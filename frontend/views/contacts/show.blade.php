@extends('layout')

@section('title', $contact->name.' · Multitena')

@section('content')
<section class="panel">
    <a class="back-link" href="{{ route('contacts.index') }}">← {{ __('Volver a contactos') }}</a>
    <div class="section-head">
        <div>
            <p class="eyebrow">{{ __('Ficha de contacto') }}</p>
            <h1>{{ $contact->name }} @if($contact->is_demo)<span class="tag">DEMO</span>@endif</h1>
            <p class="intro">{{ $contact->organization ?: __('Sin organización') }} · {{ $contact->branch->name }}</p>
        </div>
        @if(auth()->user()->role !== 'employee' || in_array('contacts.write', auth()->user()->category?->permissions ?? [], true))<a class="button button-ghost" href="{{ route('contacts.edit', $contact) }}">{{ __('Editar contacto') }}</a>@endif
    </div>
    <dl class="details">
        <div><dt>{{ __('Estado') }}</dt><dd>{{ ['prospect'=>'Prospecto','qualified'=>'Calificado','customer'=>'Cliente','inactive'=>'Inactivo'][$contact->status] ?? $contact->status }}</dd></div>
        <div><dt>{{ __('Correo') }}</dt><dd>{{ $contact->email ?: __('Sin registrar') }}</dd></div>
        <div><dt>{{ __('Teléfono') }}</dt><dd>{{ $contact->phone ?: __('Sin registrar') }}</dd></div>
        <div><dt>{{ __('Origen') }}</dt><dd>{{ $contact->source ?: __('Sin registrar') }}</dd></div>
    </dl>
    @if(auth()->user()->role !== 'employee' || in_array('opportunities.read', auth()->user()->category?->permissions ?? [], true))
    <div class="section-head section-subhead">
        <h2>{{ __('Oportunidades') }}</h2>
        @if(auth()->user()->role !== 'employee' || in_array('opportunities.write', auth()->user()->category?->permissions ?? [], true))<a class="button button-ghost" href="{{ route('opportunities.create', ['contact' => $contact->id]) }}">{{ __('Agregar oportunidad') }}</a>@endif
    </div>
    @if($contact->opportunities->isEmpty())
        <p class="empty-state">{{ __('Este contacto todavía no tiene oportunidades.') }}</p>
    @else
        <div class="table-scroll">
            <table>
                <thead><tr><th>{{ __('Oportunidad') }}</th><th>{{ __('Etapa') }}</th><th>{{ __('Monto') }}</th><th>{{ __('Cierre estimado') }}</th><th>{{ __('Acción') }}</th></tr></thead>
                <tbody>
                    @foreach($contact->opportunities as $item)
                        <tr>
                            <td>{{ $item->title }} @if($item->is_demo)<span class="tag">DEMO</span>@endif</td>
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
    @endif
    <h2>{{ __('Seguimiento') }}</h2>
    @if(auth()->user()->role !== 'employee' || in_array('contacts.write', auth()->user()->category?->permissions ?? [], true))
    <form method="post" action="{{ route('contacts.notes.store', $contact) }}" class="form-stack note-form">
        @csrf
        <label for="body">{{ __('Nueva nota') }}</label>
        <textarea id="body" name="body" rows="4" maxlength="5000" required placeholder="{{ __('Registrá una llamada, reunión o próximo paso') }}">{{ old('body') }}</textarea>
        @error('body')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        <button class="button button-primary" type="submit">{{ __('Agregar nota') }}</button>
    </form>
    @endif
    @forelse($contact->notes as $note)
        <article class="note"><div class="muted">{{ $note->author->name }} · {{ $note->created_at->format('d/m/Y H:i') }}</div><p>{{ $note->body }}</p></article>
    @empty
        <p class="empty-state">{{ __('Todavía no hay notas de seguimiento.') }}</p>
    @endforelse
</section>
@endsection
