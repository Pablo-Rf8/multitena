@extends('layout')
@section('title', 'Enlace privado emitido · Multitena')
@section('content')
<section class="panel form-panel">
    <p class="eyebrow">Entrega única</p>
    <h1>Enlace privado para {{ $link->user->name }}</h1>
    <p class="intro">{{ $link->user->company->name }} · {{ $link->user->branch?->name }}. Vence el {{ $link->expires_at->format('d/m/Y H:i:s') }}.</p>
    <p>Copiá y entregá este enlace por un canal privado. El titular deberá verificar su correo y contraseña para ver el código. Esta pantalla no vuelve a mostrar el enlace al salir.</p>
    <div class="form-stack">
        <label for="issued-link">Enlace individual</label>
        <input id="issued-link" type="text" value="{{ $url }}" readonly autocomplete="off" data-issued-link>
        <button class="button" type="button" data-copy-link>Copiar enlace</button>
        <p class="hint" data-copy-message role="status"></p>
    </div>
    <p><a href="{{ auth()->user()->role === 'super_admin' ? route('codes.admin') : route('codes.company') }}">Volver a gestión de enlaces</a></p>
</section>
@endsection
