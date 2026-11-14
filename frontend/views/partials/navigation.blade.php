@php
    $permissions = $viewer->role === 'employee' && $viewer->category?->is_active ? ($viewer->category->permissions ?? []) : [];
    $canContacts = $viewer->role !== 'employee' || in_array('contacts.read', $permissions, true);
    $canOpportunities = $viewer->role !== 'employee' || in_array('opportunities.read', $permissions, true);
    $crmOpen = request()->routeIs('contacts.*') || request()->routeIs('opportunities.*');
    $vaultOpen = request()->routeIs('credentials.*') || request()->routeIs('credential-templates.*');
    $managementOpen = request()->routeIs('branches.*') || request()->routeIs('team.*') || request()->routeIs('branch-users.*') || request()->routeIs('workforce.*');
@endphp
<nav class="side-nav" aria-label="{{ __('Secciones') }}">
    <a href="{{ route('dashboard') }}" @class(['current' => request()->routeIs('dashboard')]) @if(request()->routeIs('dashboard')) aria-current="page" @endif><svg class="icon" aria-hidden="true"><use href="#icon-home"/></svg>{{ $viewer->role === 'super_admin' ? __('Empresas') : __('Inicio') }}</a>
    @if($viewer->role === 'super_admin')
        <a href="{{ route('credentials.index') }}" @class(['current' => request()->routeIs('credentials.*')]) @if(request()->routeIs('credentials.*')) aria-current="page" @endif><svg class="icon" aria-hidden="true"><use href="#icon-key"/></svg>{{ __('Compartidas conmigo') }}</a>
        <details class="nav-disclosure" @if(request()->routeIs('companies.create') || request()->routeIs('employees.create')) open @endif>
            <summary><svg class="icon" aria-hidden="true"><use href="#icon-plus"/></svg>{{ __('Agregar') }}</summary>
            <div class="nav-children">
                <a href="{{ route('companies.create') }}" @class(['current' => request()->routeIs('companies.create')]) @if(request()->routeIs('companies.create')) aria-current="page" @endif>{{ __('Nueva empresa') }}</a>
                <a href="{{ route('employees.create') }}" @class(['current' => request()->routeIs('employees.create')]) @if(request()->routeIs('employees.create')) aria-current="page" @endif>{{ __('Registrar empleados') }}</a>
            </div>
        </details>
    @else
        <a href="{{ route('notifications.index') }}" @class(['current' => request()->routeIs('notifications.*')]) @if(request()->routeIs('notifications.*')) aria-current="page" @endif><svg class="icon" aria-hidden="true"><use href="#icon-bell"/></svg>{{ __('Notificaciones') }}</a>
        @if($canContacts || $canOpportunities)
            <details class="nav-disclosure" @if($crmOpen) open @endif>
                <summary @class(['current' => $crmOpen])><svg class="icon" aria-hidden="true"><use href="#icon-briefcase"/></svg>CRM</summary>
                <div class="nav-children">
                    @if($canContacts)<a href="{{ route('contacts.index') }}" @class(['current' => request()->routeIs('contacts.*')]) @if(request()->routeIs('contacts.*')) aria-current="page" @endif>{{ __('Contactos') }}</a>@endif
                    @if($canOpportunities)<a href="{{ route('opportunities.index') }}" @class(['current' => request()->routeIs('opportunities.*')]) @if(request()->routeIs('opportunities.*')) aria-current="page" @endif>{{ __('Oportunidades') }}</a>@endif
                </div>
            </details>
        @endif
        <details class="nav-disclosure" @if($vaultOpen) open @endif>
            <summary @class(['current' => $vaultOpen])><svg class="icon" aria-hidden="true"><use href="#icon-key"/></svg>{{ __('Credenciales') }}</summary>
            <div class="nav-children">
                <a href="{{ route('credentials.index') }}" @class(['current' => request()->routeIs('credentials.*')]) @if(request()->routeIs('credentials.*')) aria-current="page" @endif>{{ __('Mis credenciales') }}</a>
                <a href="{{ route('credential-templates.index') }}" @class(['current' => request()->routeIs('credential-templates.*')]) @if(request()->routeIs('credential-templates.*')) aria-current="page" @endif>{{ $viewer->role === 'employee' ? __('Plantillas compartidas') : __('Plantillas') }}</a>
            </div>
        </details>
        @if($viewer->role === 'company_admin')
            <details class="nav-disclosure" @if($managementOpen) open @endif>
                <summary @class(['current' => $managementOpen])><svg class="icon" aria-hidden="true"><use href="#icon-settings"/></svg>{{ __('Administración') }}</summary>
                <div class="nav-children">
                    <a href="{{ route('branches.index') }}" @class(['current' => request()->routeIs('branches.*')]) @if(request()->routeIs('branches.*')) aria-current="page" @endif>{{ __('Sucursales') }}</a>
                    <a href="{{ route('team.index') }}" @class(['current' => request()->routeIs('team.*') || request()->routeIs('branch-users.*')]) @if(request()->routeIs('team.*') || request()->routeIs('branch-users.*')) aria-current="page" @endif>{{ __('Usuarios') }}</a>
                    <a href="{{ route('workforce.index') }}" @class(['current' => request()->routeIs('workforce.*')]) @if(request()->routeIs('workforce.*')) aria-current="page" @endif>{{ __('Personal y acceso') }}</a>
                </div>
            </details>
        @endif
    @endif
</nav>
