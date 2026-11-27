<div>
    {{-- Alertas SweetAlert2 --}}
    @include('partials.swal-flash')

    {{-- Tarjeta principal --}}
    <div class="card card-outline card-primary">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h3 class="card-title mb-0">
                <i class="bi bi-box-seam me-1"></i> Catálogo de Productos
            </h3>

            <div class="d-flex flex-wrap align-items-center gap-2">
                {{-- Buscador --}}
                <div class="input-group input-group-sm" style="width: 260px;">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input
                        type="search"
                        class="form-control"
                        placeholder="Buscar por nombre o código de producto..."
                        wire:model.live.debounce.300ms="search"
                        aria-label="Buscar productos"
                    >
                </div>

                {{-- Filtro por marca --}}
                <div class="input-group input-group-sm" style="width: 200px;">
                    <span class="input-group-text"><i class="bi bi-bookmark-star"></i></span>
                    <select
                        class="form-select"
                        wire:model.live="marcaFiltro"
                        aria-label="Filtrar productos por marca"
                    >
                        <option value="">Todas las marcas</option>
                        <option value="sin-marca">Sin marca</option>
                        @foreach ($marcas as $marca)
                            <option value="{{ $marca->id }}">{{ $marca->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Nuevo producto --}}
                @can('gestionar_productos')
                    <button type="button" class="btn btn-primary btn-sm" wire:click="crear">
                        <i class="bi bi-plus-lg me-1"></i> Nuevo Producto
                    </button>
                @endcan
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Código de producto</th>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th>Marca</th>
                            <th>Unidad de Medida</th>
                            <th>Empaque</th>
                            <th class="text-end">Stock disponible</th>
                            <th class="text-end">Stock Mínimo de Alerta</th>
                            <th class="text-center" style="width: 130px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody wire:loading.class="opacity-50">
                        @forelse ($productos as $producto)
                            <tr wire:key="producto-{{ $producto->id }}">
                                <td>
                                    <span class="badge text-bg-secondary">{{ $producto->sku }}</span>
                                </td>
                                <td>{{ $producto->nombre }}</td>
                                <td>{{ $producto->categoria?->nombre ?? 'Sin categoría' }}</td>
                                <td>
                                    @if ($producto->marca)
                                        <span class="badge text-bg-light border">{{ $producto->marca->nombre }}</span>
                                    @else
                                        <span class="text-muted">Sin marca</span>
                                    @endif
                                </td>
                                <td>{{ $unidades[$producto->unidad_medida] ?? $producto->unidad_medida }}</td>
                                <td>
                                    @if ($producto->descripcion_empaque)
                                        <i class="bi bi-box-seam me-1"></i>{{ $producto->descripcion_empaque }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                @php
                                    $stockDisponible = (float) ($producto->stock_disponible ?? 0);
                                    $stockMinimo = (float) $producto->stock_minimo_alerta;
                                @endphp
                                <td class="text-end">
                                    <span class="fw-semibold {{ $stockDisponible <= $stockMinimo ? 'text-danger' : 'text-success' }}">
                                        {{ number_format($stockDisponible, 2, ',', '.') }}
                                    </span>
                                    @if ($stockDisponible <= $stockMinimo)
                                        <span class="badge text-bg-danger ms-1">Bajo mínimo</span>
                                    @endif
                                    <div class="small text-muted">
                                        {{ $unidades[$producto->unidad_medida] ?? $producto->unidad_medida }}
                                    </div>
                                </td>
                                <td class="text-end">
                                    {{ number_format($stockMinimo, 2, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    @can('gestionar_productos')
                                        <button
                                            type="button"
                                            class="btn btn-warning btn-sm"
                                            wire:click="editar({{ $producto->id }})"
                                            title="Editar"
                                        >
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-danger btn-sm"
                                            wire:click="eliminar({{ $producto->id }})"
                                            data-confirm-delete
                                            data-confirm-id="{{ $producto->id }}"
                                            data-confirm-entity="este producto"
                                            title="Eliminar"
                                        >
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    @else
                                        <span class="text-muted">Solo lectura</span>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                    No se encontraron productos.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($productos->hasPages())
            <div class="card-footer">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <small class="text-muted">
                        Mostrando {{ $productos->firstItem() ?? 0 }}–{{ $productos->lastItem() ?? 0 }}
                        de {{ $productos->total() }} productos
                    </small>
                    {{ $productos->links('pagination::bootstrap-5') }}
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
                                {{ $isEditing ? 'Editar Producto' : 'Nuevo Producto' }}
                            </h5>
                            <button type="button" class="btn-close" wire:click="cerrarModal" aria-label="Cerrar"></button>
                        </div>

                        <div class="modal-body">
                            <div class="alert alert-info d-flex align-items-start gap-2 py-2 px-3 mb-3" role="alert">
                                <i class="bi bi-info-circle-fill fs-5"></i>
                                <div>
                                    <strong>Código de producto:</strong> se genera automáticamente con las tres primeras letras del nombre y un consecutivo. Ejemplo: <strong>SAL-001</strong>.
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label for="nombre" class="form-label">
                                        Nombre <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        id="nombre"
                                        type="text"
                                        class="form-control @error('nombre') is-invalid @enderror"
                                        wire:model.live.debounce.500ms="nombre"
                                        placeholder="Ej: Harina de maíz"
                                    >
                                    @error('nombre')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label for="sku" class="form-label">
                                        Código de producto
                                    </label>
                                    <input
                                        id="sku"
                                        type="text"
                                        class="form-control"
                                        value="{{ $sku }}"
                                        placeholder="Se genera automáticamente"
                                        readonly
                                    >
                                </div>

                                <div class="col-md-6">
                                    <label for="categoria_id" class="form-label">
                                        Categoría <span class="text-danger">*</span>
                                    </label>
                                    <select
                                        id="categoria_id"
                                        class="form-select @error('categoria_id') is-invalid @enderror"
                                        wire:model="categoria_id"
                                    >
                                        <option value="">Seleccione una categoría</option>
                                        @foreach ($categorias as $categoria)
                                            <option value="{{ $categoria->id }}">{{ $categoria->codigo }} — {{ $categoria->nombre }}</option>
                                        @endforeach
                                    </select>
                                    @error('categoria_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="marca_id" class="form-label">
                                        Marca
                                    </label>
                                    <div class="input-group">
                                        <select
                                            id="marca_id"
                                            class="form-select @error('marca_id') is-invalid @enderror"
                                            wire:model="marca_id"
                                        >
                                            <option value="">Sin marca</option>
                                            @foreach ($marcas as $marca)
                                                <option value="{{ $marca->id }}">
                                                    {{ $marca->nombre }}{{ $marca->activo ? '' : ' (inactiva)' }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button
                                            type="button"
                                            class="btn btn-outline-primary"
                                            wire:click="crearMarca"
                                            title="Agregar nueva marca"
                                        >
                                            <i class="bi bi-plus-lg"></i>
                                        </button>
                                    </div>
                                    @error('marca_id')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Si la marca no aparece, créala con el botón +.</div>
                                </div>

                                <div class="col-md-4">
                                    <label for="unidad_medida" class="form-label">
                                        Unidad de Medida <span class="text-danger">*</span>
                                    </label>
                                    <select
                                        id="unidad_medida"
                                        class="form-select @error('unidad_medida') is-invalid @enderror"
                                        wire:model.live="unidad_medida"
                                    >
                                        <option value="">Seleccione una unidad</option>
                                        @foreach ($unidades as $valor => $etiqueta)
                                            <option value="{{ $valor }}">{{ $etiqueta }}</option>
                                        @endforeach
                                    </select>
                                    @error('unidad_medida')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label for="stock_minimo_alerta" class="form-label">
                                        Stock Mínimo de Alerta <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        id="stock_minimo_alerta"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="form-control @error('stock_minimo_alerta') is-invalid @enderror"
                                        wire:model="stock_minimo_alerta"
                                    >
                                    @error('stock_minimo_alerta')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label for="tipo_empaque" class="form-label">
                                        Tipo de empaque
                                    </label>
                                    <select
                                        id="tipo_empaque"
                                        class="form-select @error('tipo_empaque') is-invalid @enderror"
                                        wire:model.live="tipo_empaque"
                                    >
                                        <option value="">No aplica</option>
                                        @foreach ($tiposEmpaque as $valor => $etiqueta)
                                            <option value="{{ $valor }}">{{ $etiqueta }}</option>
                                        @endforeach
                                    </select>
                                    @error('tipo_empaque')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                @if ($tipo_empaque)
                                    <div class="col-md-4">
                                        <label for="cantidad_empaque" class="form-label">
                                            @if ($tipo_empaque === 'bulto')
                                                Cantidad que trae el bulto
                                            @else
                                                Cantidad que trae la paca
                                            @endif
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            id="cantidad_empaque"
                                            type="number"
                                            min="1"
                                            step="1"
                                            class="form-control @error('cantidad_empaque') is-invalid @enderror"
                                            wire:model="cantidad_empaque"
                                            placeholder="Ej: 24"
                                        >
                                        <div class="form-text">
                                            Se expresa en {{ $unidades[$unidad_medida] ?? 'la unidad de medida seleccionada' }}.
                                        </div>
                                        @error('cantidad_empaque')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                @endif
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

    {{-- Alta rápida de marca (modal) --}}
    @if ($mostrarModalMarca)
        <div
            class="modal fade show d-block"
            tabindex="-1"
            role="dialog"
            aria-modal="true"
            style="background-color: rgba(0, 0, 0, .5); z-index: 1060;"
            wire:click.self="cerrarModalMarca"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form wire:submit="guardarMarca">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="bi bi-bookmark-star me-1"></i> Nueva Marca
                            </h5>
                            <button type="button" class="btn-close" wire:click="cerrarModalMarca" aria-label="Cerrar"></button>
                        </div>

                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="nuevo_marca_nombre" class="form-label">
                                    Nombre <span class="text-danger">*</span>
                                </label>
                                <input
                                    id="nuevo_marca_nombre"
                                    type="text"
                                    class="form-control @error('nuevoMarcaNombre') is-invalid @enderror"
                                    wire:model="nuevoMarcaNombre"
                                    placeholder="Ej: Polar"
                                >
                                @error('nuevoMarcaNombre')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-0">
                                <label for="nuevo_marca_descripcion" class="form-label">Descripción</label>
                                <textarea
                                    id="nuevo_marca_descripcion"
                                    rows="3"
                                    class="form-control @error('nuevoMarcaDescripcion') is-invalid @enderror"
                                    wire:model="nuevoMarcaDescripcion"
                                    placeholder="Descripción de la marca"
                                ></textarea>
                                @error('nuevoMarcaDescripcion')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="cerrarModalMarca">
                                <i class="bi bi-x-lg me-1"></i> Cancelar
                            </button>

                            <button
                                type="submit"
                                class="btn btn-primary"
                                wire:loading.attr="disabled"
                                wire:target="guardarMarca"
                            >
                                <span
                                    wire:loading
                                    wire:target="guardarMarca"
                                    class="spinner-border spinner-border-sm me-1"
                                    role="status"
                                    aria-hidden="true"
                                ></span>
                                <i
                                    class="bi bi-save me-1"
                                    wire:loading.remove
                                    wire:target="guardarMarca"
                                ></i>
                                Guardar Marca
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
