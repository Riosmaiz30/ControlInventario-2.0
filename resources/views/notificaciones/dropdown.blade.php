<div class="dropdown-header">
    {{ $cantidad === 1 ? '1 recordatorio pendiente' : $cantidad.' recordatorios pendientes' }}
</div>

@forelse ($notificaciones as $notificacion)
    @php
        $programacionId = (int) ($notificacion->data['programacion_id'] ?? 0);
        $sede = $notificacion->data['sede'] ?? 'Sede sin nombre';
        $fecha = $notificacion->data['proxima_fecha'] ?? '';
    @endphp

    <form method="POST" action="{{ route('notificaciones.preparar', $programacionId) }}">
        @csrf
        <button type="submit" class="dropdown-item">
            <i class="fas fa-clock me-2"></i>
            <span class="d-block fw-semibold">Preparar envío a {{ $sede }}</span>
            @if ($fecha !== '')
                <span class="d-block text-muted small">Programado: {{ $fecha }}</span>
            @endif
        </button>
    </form>
@empty
    <span class="dropdown-item-text text-muted">
        No hay recordatorios pendientes.
    </span>
@endforelse

<div class="dropdown-divider"></div>

<form method="POST" action="{{ route('notificaciones.marcar-todas') }}">
    @csrf
    <button type="submit" class="dropdown-item dropdown-footer">
        <i class="fas fa-check-double me-1"></i> Marcar todas como leídas
    </button>
</form>
