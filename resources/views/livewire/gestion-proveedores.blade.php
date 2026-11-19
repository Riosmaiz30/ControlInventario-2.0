<div>
    {{-- Alertas SweetAlert2 --}}
    @include('partials.swal-flash')

    {{-- Tarjeta principal --}}
    <div class="card card-outline card-primary">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h3 class="card-title mb-0">
                <i class="bi bi-truck me-1"></i> Gestión de Proveedores
            </h3>

            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="input-group input-group-sm" style="width: 260px;">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input
                        type="search"
                        class="form-control"
                        placeholder="Buscar por nombre, código o RIF..."
                        wire:model.live.debounce.300ms="search"
                        aria-label="Buscar proveedores"
                    >
                </div>

                <button type="button" class="btn btn-primary btn-sm" wire:click="crear">
                    <i class="bi bi-plus-lg me-1"></i> Nuevo Proveedor
                </button>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>RIF / NIT</th>
                            <th>Teléfono</th>
                            <th>Correo</th>
                            <th>Dirección</th>
                            <th class="text-center" style="width: 130px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody wire:loading.class="opacity-50">
                        @forelse ($proveedores as $proveedor)
                            <tr wire:key="proveedor-{{ $proveedor->id }}">
                                <td><span class="badge text-bg-secondary">{{ $proveedor->codigo }}</span></td>
                                <td>{{ $proveedor->nombre }}</td>
                                <td>{{ $proveedor->rif }}</td>
                                <td>{{ $proveedor->telefono ?: '—' }}</td>
                                <td>{{ $proveedor->correo ?: '—' }}</td>
                                <td>{{ $proveedor->direccion ?: '—' }}</td>
                                <td class="text-center">
                                    <button
                                        type="button"
                                        class="btn btn-warning btn-sm"
                                        wire:click="editar({{ $proveedor->id }})"
                                        title="Editar"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-danger btn-sm"
                                        wire:click="eliminar({{ $proveedor->id }})"
                                        data-confirm-delete
                                        data-confirm-id="{{ $proveedor->id }}"
                                        data-confirm-entity="este proveedor"
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
                                    No se encontraron proveedores registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($proveedores->hasPages())
            <div class="card-footer">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <small class="text-muted">
                        Mostrando {{ $proveedores->firstItem() ?? 0 }}–{{ $proveedores->lastItem() ?? 0 }}
                        de {{ $proveedores->total() }} proveedores
                    </small>
                    {{ $proveedores->links('pagination::bootstrap-5') }}
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
                                {{ $isEditing ? 'Editar Proveedor' : 'Nuevo Proveedor' }}
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
                                        placeholder="Ej: Distribuidora ABC"
                                    >
                                    @error('nombre')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label for="rif_nit" class="form-label">
                                        RIF / NIT <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        id="rif_nit"
                                        type="text"
                                        class="form-control @error('rif_nit') is-invalid @enderror"
                                        wire:model="rif_nit"
                                        placeholder="Ej: J-12345678-9"
                                    >
                                    @error('rif_nit')
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
                                        placeholder="Ej: 0414-1234567"
                                    >
                                    @error('telefono')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="correo" class="form-label">Correo</label>
                                    <input
                                        id="correo"
                                        type="email"
                                        class="form-control @error('correo') is-invalid @enderror"
                                        wire:model="correo"
                                        placeholder="Ej: contacto@proveedor.com"
                                    >
                                    @error('correo')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="direccion" class="form-label">Dirección</label>
                                    <textarea
                                        id="direccion"
                                        rows="3"
                                        class="form-control @error('direccion') is-invalid @enderror"
                                        wire:model="direccion"
                                        placeholder="Dirección del proveedor"
                                    ></textarea>
                                    @error('direccion')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="alert alert-info d-flex align-items-start gap-2 mt-3 mb-0" role="alert">
                                <i class="bi bi-info-circle-fill mt-1"></i>
                                <span>
                                    El <strong>código</strong> se genera automáticamente con las
                                    tres primeras letras del nombre y un correlativo.
                                </span>
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
