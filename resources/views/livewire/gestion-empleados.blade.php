<div>
    {{-- Alertas SweetAlert2 --}}
    @include('partials.swal-flash')

    {{-- Tarjeta principal --}}
    <div class="card card-outline card-primary">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h3 class="card-title mb-0">
                <i class="bi bi-people me-1"></i> Gestión de Empleados
            </h3>

            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="input-group input-group-sm" style="width: 260px;">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input
                        type="search"
                        class="form-control"
                        placeholder="Buscar por cédula, nombre o cargo..."
                        wire:model.live.debounce.300ms="search"
                        aria-label="Buscar empleados"
                    >
                </div>

                <button type="button" class="btn btn-primary btn-sm" wire:click="crear">
                    <i class="bi bi-plus-lg me-1"></i> Nuevo Empleado
                </button>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Cédula</th>
                            <th>Nombres</th>
                            <th>Apellidos</th>
                            <th>Teléfono</th>
                            <th>Cargo</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center" style="width: 130px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody wire:loading.class="opacity-50">
                        @forelse ($empleados as $empleado)
                            <tr wire:key="empleado-{{ $empleado->id }}">
                                <td><span class="badge text-bg-secondary">{{ $empleado->cedula }}</span></td>
                                <td>{{ $empleado->nombres }}</td>
                                <td>{{ $empleado->apellidos }}</td>
                                <td>{{ $empleado->telefono ?: '—' }}</td>
                                <td>{{ $empleado->cargo?->nombre ?? '—' }}</td>
                                <td class="text-center">
                                    @if ($empleado->activo)
                                        <span class="badge text-bg-success">Activo</span>
                                    @else
                                        <span class="badge text-bg-secondary">Inactivo</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <button
                                        type="button"
                                        class="btn btn-warning btn-sm"
                                        wire:click="editar({{ $empleado->id }})"
                                        title="Editar"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-danger btn-sm"
                                        wire:click="eliminar({{ $empleado->id }})"
                                        data-confirm-delete
                                        data-confirm-id="{{ $empleado->id }}"
                                        data-confirm-entity="este empleado"
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
                                    No se encontraron empleados registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($empleados->hasPages())
            <div class="card-footer">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <small class="text-muted">
                        Mostrando {{ $empleados->firstItem() ?? 0 }}–{{ $empleados->lastItem() ?? 0 }}
                        de {{ $empleados->total() }} empleados
                    </small>
                    {{ $empleados->links('pagination::bootstrap-5') }}
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
            wire:keydown.escape.window="cerrarModalConEscape"
            wire:click.self="cerrarModal"
        >
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <form wire:submit="{{ $isEditing ? 'actualizar' : 'guardar' }}">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="bi bi-{{ $isEditing ? 'pencil-square' : 'plus-circle' }} me-1"></i>
                                {{ $isEditing ? 'Editar Empleado' : 'Nuevo Empleado' }}
                            </h5>
                            <button type="button" class="btn-close" wire:click="cerrarModal" aria-label="Cerrar"></button>
                        </div>

                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="cedula_numero" class="form-label">
                                        Cédula <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <select
                                            id="cedula_tipo"
                                            class="form-select @error('cedula_tipo') is-invalid @enderror"
                                            wire:model="cedula_tipo"
                                            style="max-width: 80px;"
                                            aria-label="Tipo de cédula"
                                        >
                                            @foreach ($cedulaPrefijos as $prefijo)
                                                <option value="{{ $prefijo }}">{{ $prefijo }}</option>
                                            @endforeach
                                        </select>
                                        <input
                                            id="cedula_numero"
                                            type="text"
                                            inputmode="numeric"
                                            maxlength="9"
                                            class="form-control @error('cedula_numero') is-invalid @enderror"
                                            wire:model="cedula_numero"
                                            placeholder="23702305"
                                        >
                                    </div>
                                    @error('cedula_tipo')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    @error('cedula_numero')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label for="nombres" class="form-label">
                                        Nombres <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        id="nombres"
                                        type="text"
                                        class="form-control @error('nombres') is-invalid @enderror"
                                        wire:model="nombres"
                                        placeholder="Nombres"
                                    >
                                    @error('nombres')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label for="apellidos" class="form-label">
                                        Apellidos <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        id="apellidos"
                                        type="text"
                                        class="form-control @error('apellidos') is-invalid @enderror"
                                        wire:model="apellidos"
                                        placeholder="Apellidos"
                                    >
                                    @error('apellidos')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="telefono_numero" class="form-label">Teléfono</label>
                                    <div class="input-group">
                                        <select
                                            id="telefono_prefijo"
                                            class="form-select @error('telefono_prefijo') is-invalid @enderror"
                                            wire:model="telefono_prefijo"
                                            style="max-width: 110px;"
                                            aria-label="Prefijo telefónico"
                                        >
                                            @foreach ($telefonoPrefijos as $prefijo)
                                                <option value="{{ $prefijo }}">{{ $prefijo }}</option>
                                            @endforeach
                                        </select>
                                        <input
                                            id="telefono_numero"
                                            type="text"
                                            inputmode="numeric"
                                            maxlength="7"
                                            class="form-control @error('telefono_numero') is-invalid @enderror"
                                            wire:model="telefono_numero"
                                            placeholder="1236549"
                                        >
                                    </div>
                                    @error('telefono_prefijo')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    @error('telefono_numero')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Ejemplo: 0424-1236549</div>
                                </div>

                                <div class="col-md-6">
                                    <label for="cargo_id" class="form-label">
                                        Cargo <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <select
                                            id="cargo_id"
                                            class="form-select @error('cargo_id') is-invalid @enderror"
                                            wire:model="cargo_id"
                                        >
                                            <option value="">Seleccione un cargo</option>
                                            @foreach ($cargos as $cargo)
                                                <option value="{{ $cargo->id }}">
                                                    {{ $cargo->nombre }}{{ $cargo->activo ? '' : ' (inactivo)' }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button
                                            type="button"
                                            class="btn btn-outline-primary"
                                            wire:click="crearCargo"
                                            title="Agregar nuevo cargo"
                                        >
                                            <i class="bi bi-plus-lg"></i>
                                        </button>
                                    </div>
                                    @error('cargo_id')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Si el cargo no aparece, créalo con el botón +.</div>
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
                                            Empleado activo
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

    {{-- Alta rápida de cargo (modal) --}}
    @if ($mostrarModalCargo)
        <div
            class="modal fade show d-block"
            tabindex="-1"
            role="dialog"
            aria-modal="true"
            style="background-color: rgba(0, 0, 0, .5); z-index: 1060;"
            wire:click.self="cerrarModalCargo"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form wire:submit="guardarCargo">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="bi bi-briefcase me-1"></i> Nuevo Cargo
                            </h5>
                            <button type="button" class="btn-close" wire:click="cerrarModalCargo" aria-label="Cerrar"></button>
                        </div>

                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="nuevo_cargo_nombre" class="form-label">
                                    Nombre <span class="text-danger">*</span>
                                </label>
                                <input
                                    id="nuevo_cargo_nombre"
                                    type="text"
                                    class="form-control @error('nuevoCargoNombre') is-invalid @enderror"
                                    wire:model="nuevoCargoNombre"
                                    placeholder="Ej: Jefe de Almacén"
                                >
                                @error('nuevoCargoNombre')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-0">
                                <label for="nuevo_cargo_descripcion" class="form-label">Descripción</label>
                                <textarea
                                    id="nuevo_cargo_descripcion"
                                    rows="3"
                                    class="form-control @error('nuevoCargoDescripcion') is-invalid @enderror"
                                    wire:model="nuevoCargoDescripcion"
                                    placeholder="Descripción del cargo"
                                ></textarea>
                                @error('nuevoCargoDescripcion')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="cerrarModalCargo">
                                <i class="bi bi-x-lg me-1"></i> Cancelar
                            </button>

                            <button
                                type="submit"
                                class="btn btn-primary"
                                wire:loading.attr="disabled"
                                wire:target="guardarCargo"
                            >
                                <span
                                    wire:loading
                                    wire:target="guardarCargo"
                                    class="spinner-border spinner-border-sm me-1"
                                    role="status"
                                    aria-hidden="true"
                                ></span>
                                <i
                                    class="bi bi-save me-1"
                                    wire:loading.remove
                                    wire:target="guardarCargo"
                                ></i>
                                Guardar Cargo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
