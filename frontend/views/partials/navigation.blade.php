@php
    $permissions = $viewer->role === 'employee' && $viewer->category?->is_active ? ($viewer->category->permissions ?? []) : [];
    $canContacts = $viewer->role !== 'employee' || in_array('contacts.read', $permissions, true);
    $canOpportunities = $viewer->role !== 'employee' || in_array('opportunities.read', $permissions, true);
@endphp
<nav class="side-nav" aria-label="Secciones">
    <span class="nav-group-label">{{ $viewer->role === 'super_admin' ? 'Plataforma' : 'General' }}</span>
    <a href="{{ route('dashboard') }}" @class(['current' => request()->routeIs('dashboard')]) @if(request()->routeIs('dashboard')) aria-current="page" @endif><svg class="icon" aria-hidden="true"><use href="#icon-home"/></svg>{{ $viewer->role === 'super_admin' ? 'Empresas' : 'Inicio' }}</a>
    @if($viewer->role !== 'super_admin')
        <a href="{{ route('notifications.index') }}" @class(['current' => request()->routeIs('notifications.*')]) @if(request()->routeIs('notifications.*')) aria-current="page" @endif><svg class="icon" aria-hidden="true"><use href="#icon-bell"/></svg>Notificaciones</a>
        @if($canContacts || $canOpportunities)
            <span class="nav-group-label">CRM</span>
            @if($canContacts)<a href="{{ route('contacts.index') }}" @class(['current' => request()->routeIs('contacts.*')]) @if(request()->routeIs('contacts.*')) aria-current="page" @endif><svg class="icon" aria-hidden="true"><use href="#icon-users"/></svg>Contactos</a>@endif
            @if($canOpportunities)<a href="{{ route('opportunities.index') }}" @class(['current' => request()->routeIs('opportunities.*')]) @if(request()->routeIs('opportunities.*')) aria-current="page" @endif><svg class="icon" aria-hidden="true"><use href="#icon-briefcase"/></svg>Oportunidades</a>@endif
        @endif
        @if($viewer->role === 'company_admin')
            <span class="nav-group-label">Equipo</span>
            <a href="{{ route('workforce.index') }}" @class(['current' => request()->routeIs('workforce.*')]) @if(request()->routeIs('workforce.*')) aria-current="page" @endif><svg class="icon" aria-hidden="true"><use href="#icon-shield"/></svg>Personal y acceso</a>
        @endif
    @endif
</nav>
