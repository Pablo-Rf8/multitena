@php
    $permissions = $viewer->role === 'employee' && $viewer->category?->is_active ? ($viewer->category->permissions ?? []) : [];
    $canContacts = $viewer->role !== 'employee' || in_array('contacts.read', $permissions, true);
    $canOpportunities = $viewer->role !== 'employee' || in_array('opportunities.read', $permissions, true);
@endphp
<nav class="side-nav" aria-label="Secciones">
    <a href="{{ route('dashboard') }}" @class(['current' => request()->routeIs('dashboard')]) @if(request()->routeIs('dashboard')) aria-current="page" @endif>Inicio</a>
    @if($viewer->role !== 'super_admin')
        @if($canContacts)<a href="{{ route('contacts.index') }}" @class(['current' => request()->routeIs('contacts.*')]) @if(request()->routeIs('contacts.*')) aria-current="page" @endif>Contactos</a>@endif
        @if($canOpportunities)<a href="{{ route('opportunities.index') }}" @class(['current' => request()->routeIs('opportunities.*')]) @if(request()->routeIs('opportunities.*')) aria-current="page" @endif>Oportunidades</a>@endif
        @if($viewer->role === 'company_admin')
            <a href="{{ route('workforce.index') }}" @class(['current' => request()->routeIs('workforce.*')]) @if(request()->routeIs('workforce.*')) aria-current="page" @endif>Personal y acceso</a>
        @endif
        <a href="{{ route('notifications.index') }}" @class(['current' => request()->routeIs('notifications.*')]) @if(request()->routeIs('notifications.*')) aria-current="page" @endif>Notificaciones</a>
        @if($viewer->role === 'company_admin')
            <details class="nav-more" @if(request()->routeIs('branding.*', 'activity.*')) open @endif>
                <summary>Configuración</summary>
                <a href="{{ route('branding.edit') }}" @class(['current' => request()->routeIs('branding.*')])>Identidad visual</a>
                <a href="{{ route('activity.index') }}" @class(['current' => request()->routeIs('activity.*')])>Actividad</a>
            </details>
        @endif
    @endif
</nav>
