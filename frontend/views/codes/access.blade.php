<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <title>Acceso privado · Multitena</title>
    <link rel="stylesheet" href="/assets/codes.css">
    <script src="/assets/codes.js" defer></script>
</head>
<body>
<main class="code-shell">
    <section class="code-card" data-code-access>
        <span class="code-kicker">Multitena · acceso privado</span>
        <h1>Tu generador de códigos</h1>
        <p>Verificá tu correo y contraseña para abrir el enlace individual. No necesitás instalar otra aplicación.</p>
        @if($errors->any())<div class="code-alert" role="alert">{{ $errors->first() }}</div>@endif
        <p class="code-alert" data-missing-link hidden>Falta el enlace privado. Abrí el que te entregó tu administrador.</p>
        <form method="post" action="/codes/verify" class="code-form">
            @csrf
            <input type="hidden" name="link_token" data-link-token>
            <label for="email">Correo electrónico</label>
            <input id="email" name="email" type="email" autocomplete="username" required>
            <label for="password">Contraseña de tu cuenta</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
            <button type="submit" data-verify-button disabled>Verificar identidad</button>
        </form>
        <p class="code-help">El enlace vence 120 horas después de emitirse. Tu administrador puede solicitar una renovación al superadministrador.</p>
    </section>
</main>
</body>
</html>
