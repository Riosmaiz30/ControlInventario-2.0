<div>
    {{-- Alertas SweetAlert2 --}}
    @include('partials.swal-flash')

    <div class="row g-3">
        {{-- Clave de administrador --}}
        <div class="col-lg-5">
            <div class="card card-outline card-primary h-100">
                <div class="card-header">
                    <h3 class="card-title mb-0">
                        <i class="bi bi-shield-lock me-1"></i> Clave de administrador
                    </h3>
                </div>

                <form wire:submit="guardar">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Estado de la clave:</span>
                            @if ($tieneClave)
                                <span class="badge text-bg-success">
                                    <i class="bi bi-check-circle me-1"></i> Configurada
                                </span>
                            @else
                                <span class="badge text-bg-warning">
                                    <i class="bi bi-exclamation-triangle me-1"></i> Sin configurar
                                </span>
                            @endif
                        </div>

                        <div class="alert alert-info d-flex align-items-start gap-2 py-2 px-3">
                            <i class="bi bi-info-circle-fill mt-1"></i>
                            <span>
                                Esta clave se solicitará al intentar modificar un lote registrado.
                                Guárdala en un lugar seguro.
                            </span>
                        </div>

                        @if ($tieneClave)
                            <div class="mb-3">
                                <label for="clave_actual" class="form-label">
                                    Clave actual <span class="text-danger">*</span>
                                </label>
                                <input
                                    id="clave_actual"
                                    type="password"
                                    class="form-control @error('clave_actual') is-invalid @enderror"
                                    wire:model="clave_actual"
                                    autocomplete="current-password"
                                    placeholder="Ingresa la clave actual"
                                >
                                @error('clave_actual')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif

                        <div class="mb-3">
                            <label for="nueva_clave" class="form-label">
                                {{ $tieneClave ? 'Nueva clave' : 'Clave' }} <span class="text-danger">*</span>
                            </label>
                            <input
                                id="nueva_clave"
                                type="password"
                                class="form-control @error('nueva_clave') is-invalid @enderror"
                                wire:model="nueva_clave"
                                autocomplete="new-password"
                                placeholder="Mínimo 6 caracteres"
                            >
                            <div class="form-text">Mínimo 6 caracteres.</div>
                            @error('nueva_clave')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-0">
                            <label for="nueva_clave_confirmation" class="form-label">
                                Confirmar clave <span class="text-danger">*</span>
                            </label>
                            <input
                                id="nueva_clave_confirmation"
                                type="password"
                                class="form-control @error('nueva_clave_confirmation') is-invalid @enderror"
                                wire:model="nueva_clave_confirmation"
                                autocomplete="new-password"
                                placeholder="Repite la clave"
                            >
                            @error('nueva_clave_confirmation')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="card-footer">
                        <button
                            type="submit"
                            class="btn btn-primary"
                            wire:loading.attr="disabled"
                            wire:target="guardar"
                        >
                            <span
                                wire:loading
                                wire:target="guardar"
                                class="spinner-border spinner-border-sm me-1"
                                role="status"
                                aria-hidden="true"
                            ></span>
                            <i
                                class="bi bi-save me-1"
                                wire:loading.remove
                                wire:target="guardar"
                            ></i>
                            {{ $tieneClave ? 'Cambiar clave' : 'Guardar clave' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Historial de cambios --}}
        <div class="col-lg-7">
            <div class="card card-outline card-secondary h-100">
                <div class="card-header">
                    <h3 class="card-title mb-0">
                        <i class="bi bi-clock-history me-1"></i> Últimos cambios en lotes
                    </h3>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Usuario</th>
                                    <th>Cambio</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($auditorias as $auditoria)
                                    @php
                                        $accion = match ($auditoria->accion) {
                                            'lote_actualizado' => 'Lote actualizado',
                                            default => ucfirst(str_replace('_', ' ', $auditoria->accion)),
                                        };
                                        $numeroAnterior = $auditoria->datos_anteriores['numero_lote'] ?? null;
                                        $numeroNuevo = $auditoria->datos_nuevos['numero_lote'] ?? null;
                                    @endphp

                                    <tr wire:key="auditoria-{{ $auditoria->id }}">
                                        <td class="text-nowrap">
                                            {{ $auditoria->created_at?->format('d/m/Y H:i') }}
                                        </td>
                                        <td>{{ $auditoria->user?->name ?? 'Sistema' }}</td>
                                        <td>
                                            <span class="badge text-bg-secondary">{{ $accion }}</span>
                                            <div class="small mt-1">{{ $auditoria->descripcion }}</div>

                                            @if ($numeroAnterior && $numeroNuevo && $numeroAnterior !== $numeroNuevo)
                                                <div class="small text-muted">
                                                    Nº anterior: {{ $numeroAnterior }}
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-5">
                                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                            No hay cambios registrados.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
