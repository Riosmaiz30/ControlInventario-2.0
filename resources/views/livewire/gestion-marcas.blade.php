<div>
    {{-- Alertas SweetAlert2 --}}
    @include('partials.swal-flash')

    {{-- Tarjeta principal --}}
    <div class="card card-outline card-primary">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h3 class="card-title mb-0">
                <i class="bi bi-bookmark-star me-1"></i> Gestión de Marcas
            </h3>

            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="input-group input-group-sm" style="width: 240px;">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input
                        type="search"
                        class="form-control"
                        placeholder="Buscar marca..."
                        wire:model.live.debounce.300ms="search"
                        aria-label="Buscar marcas"
                    >
                </div>

                <button type="button" class="btn btn-primary btn-sm" wire:click="crear">
                    <i class="bi bi-plus-lg me-1"></i> Nueva Marca
                </button>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 120px;">Código</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th class="text-center" style="width: 120px;">Productos</th>
                            <th class="text-center" style="width: 120px;">Estado</th>
                            <th class="text-center" style="width: 130px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody wire:loading.class="opacity-50">
                        @forelse ($marcas as $marca)
                            <tr wire:key="marca-{{ $marca->id }}">
                                <td>
                                    <span class="badge text-bg-secondary">{{ $marca->codigo }}</span>
                                </td>
                                <td class="fw-semibold">{{ $marca->nombre }}</td>
                                <td>{{ $marca->descripcion ?: '—' }}</td>
                                <td class="text-center">
                                    <span class="badge text-bg-info">{{ $marca->productos_count }}</span>
                                </td>
                                <td class="text-center">
                                    @if ($marca->activo)
                                        <span class="badge text-bg-success">Activa</span>
                                    @else
                                        <span class="badge text-bg-secondary">Inactiva</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <button
                                        type="button"
                                        class="btn btn-warning btn-sm"
                                        wire:click="editar({{ $marca->id }})"
                                        title="Editar"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-danger btn-sm"
                                        wire:click="eliminar({{ $marca->id }})"
                                        data-confirm-delete
                                        data-confirm-id="{{ $marca->id }}"
                                        data-confirm-entity="esta marca"
                                        title="Eliminar"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                    No se encontraron marcas registradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($marcas->hasPages())
            <div class="card-footer">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <small class="text-muted">
                        Mostrando {{ $marcas->firstItem() ?? 0 }}–{{ $marcas->lastItem() ?? 0 }}
                        de {{ $marcas->total() }} marcas
                    </small>
                    {{ $marcas->links('pagination::bootstrap-5') }}
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
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form wire:submit="{{ $isEditing ? 'actualizar' : 'guardar' }}">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="bi bi-{{ $isEditing ? 'pencil-square' : 'plus-circle' }} me-1"></i>
                                {{ $isEditing ? 'Editar Marca' : 'Nueva Marca' }}
                            </h5>
                            <button type="button" class="btn-close" wire:click="cerrarModal" aria-label="Cerrar"></button>
                        </div>

                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="nombre" class="form-label">
                                    Nombre <span class="text-danger">*</span>
                                </label>
                                <input
                                    id="nombre"
                                    type="text"
                                    class="form-control @error('nombre') is-invalid @enderror"
                                    wire:model="nombre"
                                    placeholder="Ej: Polar"
                                >
                                @error('nombre')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <textarea
                                    id="descripcion"
                                    rows="4"
                                    class="form-control @error('descripcion') is-invalid @enderror"
                                    wire:model="descripcion"
                                    placeholder="Descripción de la marca"
                                ></textarea>
                                @error('descripcion')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-check form-switch">
                                <input
                                    id="activo"
                                    type="checkbox"
                                    class="form-check-input @error('activo') is-invalid @enderror"
                                    wire:model="activo"
                                >
                                <label for="activo" class="form-check-label">Marca activa</label>
                                @error('activo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
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
