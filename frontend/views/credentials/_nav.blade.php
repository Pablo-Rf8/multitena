<nav class="section-tabs" aria-label="{{ __('Gestión de Credenciales') }}">
    <a href="{{ route('credentials.show', $credential) }}" @if(request()->routeIs('credentials.show')) aria-current="page" @endif>{{ __('Datos') }}</a>
    <a href="{{ route('credentials.sharing', $credential) }}" @if(request()->routeIs('credentials.sharing')) aria-current="page" @endif>{{ __('Compartir') }}</a>
    <a href="{{ route('credentials.permissions', $credential) }}" @if(request()->routeIs('credentials.permissions')) aria-current="page" @endif>{{ __('Permisos') }}</a>
    <a href="{{ route('credentials.history', $credential) }}" @if(request()->routeIs('credentials.history')) aria-current="page" @endif>{{ __('Historial') }}</a>
</nav>
