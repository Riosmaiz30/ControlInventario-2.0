<div>
    {{-- Alertas SweetAlert2 --}}
    @include('partials.swal-flash')

    {{-- Tarjeta principal --}}
    <div class="card card-outline card-primary">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h3 class="card-title mb-0">
                <i class="bi bi-geo-alt me-1"></i> Gestión de Sedes
            </h3>

            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="input-group input-group-sm" style="width: 280px;">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input
                        type="search"
                        class="form-control"
                        placeholder="Buscar sede o responsable..."
                        wire:model.live.debounce.300ms="search"
                        aria-label="Buscar sedes"
                    >
                </div>

                <button type="button" class="btn btn-primary btn-sm" wire:click="crear">
                    <i class="bi bi-plus-lg me-1"></i> Nueva Sede
                </button>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Responsable</th>
                            <th>Dirección</th>
                            <th>Teléfono</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center" style="width: 130px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody wire:loading.class="opacity-50">
                        @forelse ($sedes as $sede)
                            <tr wire:key="sede-{{ $sede->id }}">
                                <td class="fw-semibold">{{ $sede->nombre }}</td>
                                <td>
                                    @if ($sede->tipo === 'principal')
                                        <span class="badge text-bg-primary">Principal</span>
                                    @else
                                        <span class="badge text-bg-info">Consumidor</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($sede->responsable)
                                        {{ $sede->responsable->nombres }} {{ $sede->responsable->apellidos }}
                                    @else
                                        <span class="text-muted">Sin asignar</span>
                                    @endif
                                </td>
                                <td>{{ $sede->direccion }}</td>
                                <td>{{ $sede->telefono ?: '—' }}</td>
                                <td class="text-center">
                                    @if ($sede->activo)
                                        <span class="badge text-bg-success">Activa</span>
                                    @else
                                        <span class="badge text-bg-secondary">Inactiva</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <button
                                        type="button"
                                        class="btn btn-warning btn-sm"
                                        wire:click="editar({{ $sede->id }})"
                                        title="Editar"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-danger btn-sm"
                                        wire:click="eliminar({{ $sede->id }})"
                                        data-confirm-delete
                                        data-confirm-id="{{ $sede->id }}"
                                        data-confirm-entity="esta sede"
                                        title="Eliminar"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                    No se encontraron sedes registradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($sedes->hasPages())
            <div class="card-footer">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <small class="text-muted">
                        Mostrando {{ $sedes->firstItem() ?? 0 }}–{{ $sedes->lastItem() ?? 0 }}
                        de {{ $sedes->total() }} sedes
                    </small>
                    {{ $sedes->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @endif
    </div>

    {{-- Formulario (modal) --}}
    @if ($mostrarModal)
        <div
            class="modal fade show d-block"
            tabindex="-1"
            role="dialog"
            aria-modal="true"
            style="background-color: rgba(0, 0, 0, .5);"
            wire:keydown.escape.window="cerrarModal"
            wire:click.self="cerrarModal"
        >
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <form wire:submit="{{ $isEditing ? 'actualizar' : 'guardar' }}">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="bi bi-{{ $isEditing ? 'pencil-square' : 'plus-circle' }} me-1"></i>
                                {{ $isEditing ? 'Editar Sede' : 'Nueva Sede' }}
                            </h5>
                            <button type="button" class="btn-close" wire:click="cerrarModal" aria-label="Cerrar"></button>
                        </div>

                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label for="nombre" class="form-label">
                                        Nombre <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        id="nombre"
                                        type="text"
                                        class="form-control @error('nombre') is-invalid @enderror"
                                        wire:model="nombre"
                                        placeholder="Ej: Sede Principal"
                                    >
                                    @error('nombre')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label for="tipo" class="form-label">
                                        Tipo <span class="text-danger">*</span>
                                    </label>
                                    <select
                                        id="tipo"
                                        class="form-select @error('tipo') is-invalid @enderror"
                                        wire:model="tipo"
                                    >
                                        <option value="principal">Principal</option>
                                        <option value="consumidor">Consumidor</option>
                                    </select>
                                    @error('tipo')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="empleado_id" class="form-label">Responsable</label>
                                    <select
                                        id="empleado_id"
                                        class="form-select @error('empleado_id') is-invalid @enderror"
                                        wire:model="empleado_id"
                                    >
                                        <option value="">Sin asignar</option>
                                        @foreach ($empleados as $empleado)
                                            <option value="{{ $empleado->id }}">
                                                {{ $empleado->nombres }} {{ $empleado->apellidos }}
                                                @if (! $empleado->activo)
                                                    (Inactivo)
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('empleado_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="telefono" class="form-label">Teléfono</label>
                                    <input
                                        id="telefono"
                                        type="text"
                                        class="form-control @error('telefono') is-invalid @enderror"
                                        wire:model="telefono"
                                        placeholder="Ej: 0212-1234567"
                                    >
                                    @error('telefono')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="direccion" class="form-label">
                                        Dirección <span class="text-danger">*</span>
                                    </label>
                                    <textarea
                                        id="direccion"
                                        rows="3"
                                        class="form-control @error('direccion') is-invalid @enderror"
                                        wire:model="direccion"
                                        placeholder="Dirección de la sede"
                                    ></textarea>
                                    @error('direccion')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input
                                            id="activo"
                                            type="checkbox"
                                            class="form-check-input @error('activo') is-invalid @enderror"
                                            wire:model="activo"
                                        >
                                        <label for="activo" class="form-check-label">
                                            Sede activa
                                        </label>
                                        @error('activo')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="cerrarModal">
                                <i class="bi bi-x-lg me-1"></i> Cancelar
                            </button>

                            <button
                                type="submit"
                                class="btn btn-primary"
                                wire:loading.attr="disabled"
                                wire:target="guardar,actualizar"
                            >
                                <span
                                    wire:loading
                                    wire:target="guardar,actualizar"
                                    class="spinner-border spinner-border-sm me-1"
                                    role="status"
                                    aria-hidden="true"
                                ></span>
                                <i
                                    class="bi bi-save me-1"
                                    wire:loading.remove
                                    wire:target="guardar,actualizar"
                                ></i>
                                {{ $isEditing ? 'Actualizar' : 'Guardar' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
