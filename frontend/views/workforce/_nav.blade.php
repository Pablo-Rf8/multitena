<nav class="section-tabs" aria-label="{{ __('Personal y acceso') }}">
@foreach([
    'workforce.index' => __('Empleados'),
    'workforce.categories.index' => __('Categorías y permisos'),
    'workforce.settings.edit' => __('Acceso'),
    'workforce.devices.index' => __('Dispositivos'),
    'workforce.sessions.index' => __('Sesiones'),
] as $route => $label)
    <a href="{{ route($route) }}" @if(request()->routeIs($route)) aria-current="page" @endif>{{ $label }}</a>
@endforeach
</nav>
