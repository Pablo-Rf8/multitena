@php
    $permissions = $viewer->role === 'employee' && $viewer->category?->is_active ? ($viewer->category->permissions ?? []) : [];
    $canContacts = $viewer->role !== 'employee' || in_array('contacts.read', $permissions, true);
    $canOpportunities = $viewer->role !== 'employee' || in_array('opportunities.read', $permissions, true);
@endphp
<nav class="side-nav" aria-label="Secciones">
    <span class="nav-group-label">{{ $viewer->role === 'super_admin' ? __('Plataforma') : __('General') }}</span>
    <a href="{{ route('dashboard') }}" @class(['current' => request()->routeIs('dashboard')]) @if(request()->routeIs('dashboard')) aria-current="page" @endif><svg class="icon" aria-hidden="true"><use href="#icon-home"/></svg>{{ $viewer->role === 'super_admin' ? __('Empresas') : __('Inicio') }}</a>
    @if($viewer->role === 'super_admin')
        <span class="nav-group-label">{{ __('Gestión') }}</span>
        <a href="{{ route('credentials.index') }}" @class(['current' => request()->routeIs('credentials.*')]) @if(request()->routeIs('credentials.*')) aria-current="page" @endif><svg class="icon" aria-hidden="true"><use href="#icon-shield"/></svg>{{ __('Compartidas conmigo') }}</a>
        <a href="{{ route('companies.create') }}" @class(['current' => request()->routeIs('companies.create')]) @if(request()->routeIs('companies.create')) aria-current="page" @endif><svg class="icon" aria-hidden="true"><use href="#icon-plus"/></svg>{{ __('Nueva empresa') }}</a>
        <a href="{{ route('employees.create') }}" @class(['current' => request()->routeIs('employees.create')]) @if(request()->routeIs('employees.create')) aria-current="page" @endif><svg class="icon" aria-hidden="true"><use href="#icon-users"/></svg>{{ __('Registrar empleados') }}</a>
    @else
        <a href="{{ route('notifications.index') }}" @class(['current' => request()->routeIs('notifications.*')]) @if(request()->routeIs('notifications.*')) aria-current="page" @endif><svg class="icon" aria-hidden="true"><use href="#icon-bell"/></svg>{{ __('Notificaciones') }}</a>
        <a href="{{ route('credentials.index') }}" @class(['current' => request()->routeIs('credentials.*')]) @if(request()->routeIs('credentials.*')) aria-current="page" @endif><svg class="icon" aria-hidden="true"><use href="#icon-shield"/></svg>{{ __('Gestión de Credenciales') }}</a>
        <a href="{{ route('credential-templates.index') }}" @class(['current' => request()->routeIs('credential-templates.*')]) @if(request()->routeIs('credential-templates.*')) aria-current="page" @endif><svg class="icon" aria-hidden="true"><use href="#icon-plus"/></svg>{{ $viewer->role === 'employee' ? __('Plantillas compartidas') : __('Plantillas') }}</a>
        @if($canContacts || $canOpportunities)
            <span class="nav-group-label">CRM</span>
            @if($canContacts)<a href="{{ route('contacts.index') }}" @class(['current' => request()->routeIs('contacts.*')]) @if(request()->routeIs('contacts.*')) aria-current="page" @endif><svg class="icon" aria-hidden="true"><use href="#icon-users"/></svg>{{ __('Contactos') }}</a>@endif
            @if($canOpportunities)<a href="{{ route('opportunities.index') }}" @class(['current' => request()->routeIs('opportunities.*')]) @if(request()->routeIs('opportunities.*')) aria-current="page" @endif><svg class="icon" aria-hidden="true"><use href="#icon-briefcase"/></svg>{{ __('Oportunidades') }}</a>@endif
        @endif
        @if($viewer->role === 'company_admin')
            <span class="nav-group-label">{{ __('Equipo') }}</span>
            <a href="{{ route('workforce.index') }}" @class(['current' => request()->routeIs('workforce.*')]) @if(request()->routeIs('workforce.*')) aria-current="page" @endif><svg class="icon" aria-hidden="true"><use href="#icon-shield"/></svg>{{ __('Personal y acceso') }}</a>
        @endif
    @endif
</nav>
