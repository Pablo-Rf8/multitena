@php
    $labels = [
        'company.created' => 'Empresa creada', 'company.updated' => 'Empresa actualizada',
        'company.activated' => 'Empresa activada', 'company.suspended' => 'Empresa suspendida',
        'branch.created' => 'Sucursal creada', 'branch.updated' => 'Sucursal actualizada',
        'branch.activated' => 'Sucursal activada', 'branch.suspended' => 'Sucursal suspendida',
        'user.created' => 'Usuario creado', 'user.updated' => 'Usuario actualizado',
        'user.activated' => 'Usuario activado', 'user.suspended' => 'Usuario suspendido',
        'contact.created' => 'Contacto creado', 'contact.updated' => 'Contacto actualizado',
        'opportunity.created' => 'Oportunidad creada', 'opportunity.updated' => 'Oportunidad actualizada',
        'note.created' => 'Nota agregada',
        'password.changed' => 'Contraseña cambiada', 'password.reset' => 'Contraseña restablecida',
    ];
@endphp
@if($activity->isEmpty())
    <p class="empty-state">Todavía no hay actividad registrada.</p>
@else
    <div class="table-scroll">
        <table>
            <thead><tr><th>Fecha</th><th>Acción</th><th>Autor</th><th>Detalle</th></tr></thead>
            <tbody>
                @foreach($activity as $entry)
                    <tr>
                        <td>{{ $entry->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $labels[$entry->event] ?? $entry->event }}</td>
                        <td>{{ $entry->actor?->name ?? 'Sistema' }}</td>
                        <td>{{ $entry->properties['name'] ?? $entry->properties['title'] ?? $entry->properties['email'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
