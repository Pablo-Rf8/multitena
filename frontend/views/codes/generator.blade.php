<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <title>Código temporal · {{ $link->user->company->name }}</title>
    <link rel="stylesheet" href="/assets/codes.css">
    <script src="/assets/codes.js" defer></script>
</head>
<body style="--code-brand: {{ $link->user->company->theme_primary }};">
<main class="code-shell">
    <section class="code-card" data-code-generator data-seconds="{{ $seconds }}">
        <span class="code-kicker">Multitena · código individual</span>
        <h1>{{ $link->user->name }}</h1>
        <p>{{ $link->user->company->name }} · {{ $link->user->branch?->name }}</p>
        <div class="code-status" data-code-status>{{ ['active'=>'Enlace activo','suspended'=>'Enlace suspendido','revoked'=>'Enlace revocado','expired'=>'Enlace vencido','account_disabled'=>'Cuenta sin acceso'][$status] ?? 'Acceso no disponible' }}</div>
        <output class="code-value" data-code-value aria-live="polite">{{ $code ?? '••••••' }}</output>
        <p class="code-countdown" data-code-countdown>@if($status === 'active'){{ $code ? 'Cambia en '.$seconds.' segundos' : 'Código utilizado. Esperá '.$seconds.' segundos.' }}@else Solicitá un enlace nuevo al administrador. @endif</p>
        <div class="code-progress" aria-hidden="true"><span data-code-progress style="width: {{ $status === 'active' ? ($seconds / 30 * 100) : 0 }}%"></span></div>
        <p class="code-help">Enlace emitido: {{ $link->issued_at->format('d/m/Y H:i:s') }}<br>Vence: {{ $link->expires_at->format('d/m/Y H:i:s') }}</p>
        <p class="code-help">El código se actualiza automáticamente. Cada valor se acepta una sola vez para iniciar sesión.</p>
    </section>
</main>
</body>
</html>
