@php
    $permissions = $viewer->role === 'employee' && $viewer->category?->is_active ? ($viewer->category->permissions ?? []) : [];
    $canContacts = $viewer->role !== 'employee' || in_array('contacts.read', $permissions, true);
    $canOpportunities = $viewer->role !== 'employee' || in_array('opportunities.read', $permissions, true);
@endphp
<nav class="side-nav" aria-label="Secciones">
    <span class="nav-group-label">{{ $viewer->role === 'super_admin' ? 'Plataforma' : 'Mi empresa' }}</span>
    <a href="{{ route('dashboard') }}" @class(['current' => request()->routeIs('dashboard')]) @if(request()->routeIs('dashboard')) aria-current="page" @endif><span aria-hidden="true">▦</span> Panel</a>
    @if($viewer->company_id)<a href="{{ route('notifications.index') }}" @class(['current' => request()->routeIs('notifications.*')])><span aria-hidden="true">◉</span> Notificaciones</a>@endif
    @if($viewer->role === 'super_admin')
        <a href="{{ route('codes.admin') }}" @class(['current' => request()->routeIs('codes.admin', 'codes.history')])><span aria-hidden="true">◫</span> Gestión de códigos y accesos</a>
        <a href="{{ route('companies.create') }}" @class(['current' => request()->routeIs('companies.create')])><span aria-hidden="true">＋</span> Nueva empresa</a>
        <a href="{{ route('employees.create') }}" @class(['current' => request()->routeIs('employees.*')])><span aria-hidden="true">♙</span> Registrar empleados</a>
    @else
        @if($canContacts)<a href="{{ route('contacts.index') }}" @class(['current' => request()->routeIs('contacts.*')])><span aria-hidden="true">◎</span> Contactos</a>@endif
        @if($canOpportunities)<a href="{{ route('opportunities.index') }}" @class(['current' => request()->routeIs('opportunities.*')])><span aria-hidden="true">◈</span> Oportunidades</a>@endif
        @if($viewer->role === 'company_admin')
            <span class="nav-group-label">Administración</span>
            <a href="{{ route('branches.create') }}" @class(['current' => request()->routeIs('branches.*')])><span aria-hidden="true">⌂</span> Sucursales</a>
            <a href="{{ route('branch-users.create') }}" @class(['current' => request()->routeIs('branch-users.*')])><span aria-hidden="true">♧</span> Usuarios</a>
            <a href="{{ route('workforce.index') }}" @class(['current' => request()->routeIs('workforce.*')])><span aria-hidden="true">☷</span> Personal y acceso</a>
            <a href="{{ route('branding.edit') }}" @class(['current' => request()->routeIs('branding.*')])><span aria-hidden="true">◐</span> Identidad visual</a>
            <a href="{{ route('activity.index') }}" @class(['current' => request()->routeIs('activity.*')])><span aria-hidden="true">◷</span> Actividad</a>
        @endif
        @if(in_array($viewer->role, ['company_admin', 'branch_admin'], true))
            <a href="{{ route('codes.company') }}" @class(['current' => request()->routeIs('codes.company')])><span aria-hidden="true">▣</span> Enlaces privados</a>
        @endif
    @endif
</nav>
