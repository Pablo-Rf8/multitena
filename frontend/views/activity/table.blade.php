@php
    $labels = [
        'company.created' => __('Empresa creada'), 'company.updated' => __('Empresa actualizada'),
        'company.activated' => __('Empresa activada'), 'company.suspended' => __('Empresa suspendida'),
        'branch.created' => __('Sucursal creada'), 'branch.updated' => __('Sucursal actualizada'),
        'branch.activated' => __('Sucursal activada'), 'branch.suspended' => __('Sucursal suspendida'),
        'user.created' => __('Usuario creado'), 'user.updated' => __('Usuario actualizado'),
        'user.activated' => __('Usuario activado'), 'user.suspended' => __('Usuario suspendido'),
        'contact.created' => __('Contacto creado'), 'contact.updated' => __('Contacto actualizado'),
        'opportunity.created' => __('Oportunidad creada'), 'opportunity.updated' => __('Oportunidad actualizada'),
        'note.created' => __('Nota agregada'),
        'password.changed' => __('Contraseña cambiada'), 'password.reset' => __('Contraseña restablecida'),
        'credential_template.created' => __('Plantilla creada'), 'credential_template.updated' => __('Plantilla actualizada'), 'credential_template.deleted' => __('Plantilla eliminada'),
        'credential.created' => __('Credencial creada'), 'credential.updated' => __('Credencial actualizada'), 'credential.deleted' => __('Credencial eliminada'),
        'credential.viewed' => __('Credencial consultada'), 'credential.secret_revealed' => __('Contraseña mostrada'),
        'credential.grant_updated' => __('Permiso de credencial actualizado'), 'credential.grant_revoked' => __('Permiso de credencial revocado'),
    ];
@endphp
@if($activity->isEmpty())
    <p class="empty-state">{{ __('Todavía no hay actividad registrada.') }}</p>
@else
    <div class="table-scroll">
        <table>
            <thead><tr><th>{{ __('Fecha') }}</th><th>{{ __('Acción') }}</th><th>{{ __('Autor') }}</th><th>{{ __('Detalle') }}</th></tr></thead>
            <tbody>
                @foreach($activity as $entry)
                    <tr>
                        <td>{{ $entry->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $labels[$entry->event] ?? $entry->event }}</td>
                        <td>{{ $entry->actor?->name ?? __('Sistema') }}</td>
                        <td>{{ $entry->properties['name'] ?? $entry->properties['title'] ?? $entry->properties['email'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
