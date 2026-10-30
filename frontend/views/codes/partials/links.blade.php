<div class="table-scroll">
    <table>
        <thead><tr><th>Usuario</th><th>Empresa y sucursal</th><th>Estado</th><th>Emitido</th><th>Vence</th><th>Tiempo restante</th><th>Acción</th></tr></thead>
        <tbody>
            @forelse($links as $link)
                @php($state = $link->status === 'active' && $link->expires_at->isPast() ? 'expired' : $link->status)
                <tr>
                    <td><strong>{{ $link->user?->name ?? 'Usuario eliminado' }}</strong><br><small>{{ $link->user?->email }} · {{ $link->user?->role }}</small></td>
                    <td>{{ $link->company?->name }}<br><small>{{ $link->branch?->name ?? '—' }}</small></td>
                    <td><span @class(['tag', 'tag-muted' => $state !== 'active'])>{{ ['active'=>'Activo','expired'=>'Vencido','suspended'=>'Suspendido','revoked'=>'Revocado'][$state] ?? $state }}</span></td>
                    <td>{{ $link->issued_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $link->expires_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $state === 'active' ? $link->expires_at->diffForHumans(now(), true) : '—' }}</td>
                    <td>
                        @if($manage)
                            <div class="code-actions">
                                <form method="post" action="{{ route('codes.renew', $link) }}">@csrf<button class="text-button" type="submit">Renovar</button></form>
                                @if($state === 'active')<form method="post" action="{{ route('codes.suspend', $link) }}" data-confirm="¿Suspender el enlace y finalizar las sesiones del usuario?">@csrf<button class="text-button" type="submit">Suspender</button></form>@endif
                                @if($link->status !== 'revoked')<form method="post" action="{{ route('codes.revoke', $link) }}" data-confirm="¿Revocar este enlace y finalizar las sesiones del usuario?">@csrf<button class="text-button" type="submit">Revocar</button></form>@endif
                            </div>
                        @elseif($canClaim && $state === 'active' && $link->handoff_token)
                            <form method="post" action="{{ route('codes.claim', $link) }}">@csrf<button class="text-button" type="submit">Recibir enlace una vez</button></form>
                        @else <span class="muted">Sin enlace pendiente de entrega</span> @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty-state">Todavía no hay enlaces para estos filtros.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
{{ $links->links() }}
