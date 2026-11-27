<div>
    {{-- Alertas SweetAlert2 --}}
    @include('partials.swal-flash')

    {{-- Tarjeta principal --}}
    <div class="card card-outline card-primary">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h3 class="card-title mb-0">
                <i class="bi bi-boxes me-1"></i> Entradas de Mercancía por Lotes
            </h3>

            <div class="d-flex flex-wrap align-items-center gap-2">
                {{-- Buscador --}}
                <div class="input-group input-group-sm" style="width: 280px;">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input
                        type="search"
                        class="form-control"
                        placeholder="Buscar por Nº de lote o producto..."
                        wire:model.live.debounce.300ms="search"
                        aria-label="Buscar lotes"
                    >
                </div>

                {{-- Nueva entrada --}}
                @can('registrar_lotes')
                    <button type="button" class="btn btn-primary btn-sm" wire:click="crear">
                        <i class="bi bi-plus-lg me-1"></i> Nueva Entrada / Lote
                    </button>
                @endcan
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nº de Lote</th>
                            <th>Producto</th>
                            <th>Proveedor</th>
                            <th class="text-end">Stock Actual / Inicial</th>
                            <th>Fecha de Registro</th>
                            <th>Fecha de Vencimiento</th>
                            <th class="text-center" style="width: 90px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody wire:loading.class="opacity-50">
                        @php $hoy = now()->startOfDay(); @endphp

                        @forelse ($lotes as $lote)
                            @php
                                $vencimiento = $lote->fecha_vencimiento;
                                $vencido = $vencimiento && $vencimiento->lt($hoy);
                                $porVencer = $vencimiento && ! $vencido && $vencimiento->lte($hoy->copy()->addDays(30));
                                $dias = $vencimiento ? (int) $hoy->diffInDays($vencimiento->copy()->startOfDay(), false) : null;
                            @endphp

                            <tr
                                wire:key="lote-{{ $lote->id }}"
                                @class(['table-danger' => $vencido, 'table-warning' => $porVencer])
                            >
                                <td>
                                    <span class="badge text-bg-secondary">{{ $lote->numero_lote }}</span>
                                </td>
                                <td>{{ $lote->producto?->nombre ?? 'Producto eliminado' }}</td>
                                <td>{{ $lote->proveedor?->nombre ?? 'Proveedor eliminado' }}</td>
                                <td class="text-end">
                                    <span class="fw-semibold">
                                        {{ number_format((float) $lote->stock_actual, 2, ',', '.') }}
                                        /
                                        {{ number_format((float) $lote->stock_inicial, 2, ',', '.') }}
                                    </span>
                                    @if ((float) $lote->stock_actual <= 0)
                                        <span class="badge text-bg-secondary ms-1">Agotado</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $lote->fecha_registro?->format('d/m/Y') ?? '—' }}
                                </td>
                                <td>
                                    @if ($vencimiento)
                                        <div class="d-flex flex-column align-items-start">
                                            <span class="fw-semibold">{{ $vencimiento->format('d/m/Y') }}</span>

                                            @if ($vencido)
                                                <span class="badge text-bg-danger mt-1">
                                                    Vencido hace {{ abs($dias) }} día(s)
                                                </span>
                                            @elseif ($porVencer)
                                                <span class="badge text-bg-warning mt-1">
                                                    {{ $dias === 0 ? 'Vence hoy' : 'Vence en ' . $dias . ' día(s)' }}
                                                </span>
                                            @else
                                                <span class="badge text-bg-success mt-1">Vigente</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted">Sin vencimiento</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @can('modificar_lotes')
                                        <button
                                            type="button"
                                            class="btn btn-warning btn-sm"
                                            data-confirm-edit
                                            data-edit-id="{{ $lote->id }}"
                                            data-clave-configurada="{{ $claveConfigurada ? '1' : '0' }}"
                                            title="Modificar lote"
                                        >
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                    No se encontraron lotes registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($lotes->hasPages())
            <div class="card-footer">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <small class="text-muted">
                        Mostrando {{ $lotes->firstItem() ?? 0 }}–{{ $lotes->lastItem() ?? 0 }}
                        de {{ $lotes->total() }} lotes
                    </small>
                    {{ $lotes->links('pagination::bootstrap-5') }}
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
                                <i class="bi bi-{{ $isEditing ? 'pencil-square' : 'box-arrow-in-down' }} me-1"></i>
                                {{ $isEditing ? 'Editar Entrada / Lote' : 'Nueva Entrada / Lote' }}
                            </h5>
                            <button type="button" class="btn-close" wire:click="cerrarModal" aria-label="Cerrar"></button>
                        </div>

                        <div class="modal-body">
                            <div class="alert alert-info d-flex align-items-start gap-2 mb-3" role="alert">
                                <i class="bi bi-info-circle-fill mt-1"></i>
                                <span>
                                    @if ($isEditing)
                                        Si cambias el <strong>proveedor</strong> o la <strong>fecha de registro</strong>,
                                        el número de lote se regenerará automáticamente. El stock actual se ajustará
                                        conservando los movimientos ya realizados.
                                    @else
                                        El <strong>número de lote</strong> se genera automáticamente al seleccionar
                                        el proveedor, usando sus iniciales, la fecha de registro y un consecutivo.
                                        El <strong>stock actual</strong> se inicializará con el mismo valor del
                                        <strong>stock inicial</strong>.
                                    @endif
                                </span>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="producto_id" class="form-label">
                                        Producto <span class="text-danger">*</span>
                                    </label>
                                    <select
                                        id="producto_id"
                                        class="form-select @error('producto_id') is-invalid @enderror"
                                        wire:model="producto_id"
                                    >
                                        <option value="">Seleccione un producto</option>
                                        @foreach ($productos as $producto)
                                            <option value="{{ $producto->id }}">
                                                {{ $producto->nombre }} ({{ $producto->sku }})
                                                @if ($producto->descripcion_empaque)
                                                    — {{ $producto->descripcion_empaque }}
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('producto_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="proveedor_id" class="form-label">
                                        Proveedor <span class="text-danger">*</span>
                                    </label>
                                    <select
                                        id="proveedor_id"
                                        class="form-select @error('proveedor_id') is-invalid @enderror"
                                        wire:model.live="proveedor_id"
                                    >
                                        <option value="">Seleccione un proveedor</option>
                                        @foreach ($proveedores as $proveedor)
                                            <option value="{{ $proveedor->id }}">
                                                {{ $proveedor->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('proveedor_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="numero_lote" class="form-label">
                                        Nº de Lote <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        id="numero_lote"
                                        type="text"
                                        class="form-control @error('numero_lote') is-invalid @enderror"
                                        wire:model="numero_lote"
                                        readonly
                                    >
                                    @error('numero_lote')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="stock_inicial" class="form-label">
                                        Stock Inicial <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        id="stock_inicial"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        class="form-control @error('stock_inicial') is-invalid @enderror"
                                        wire:model="stock_inicial"
                                        placeholder="0.00"
                                    >
                                    @error('stock_inicial')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="fecha_registro" class="form-label">
                                        Fecha de Registro <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        id="fecha_registro"
                                        type="date"
                                        class="form-control @error('fecha_registro') is-invalid @enderror"
                                        wire:model.live="fecha_registro"
                                    >
                                    @error('fecha_registro')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="fecha_vencimiento" class="form-label">
                                        Fecha de Vencimiento <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        id="fecha_vencimiento"
                                        type="date"
                                        class="form-control @error('fecha_vencimiento') is-invalid @enderror"
                                        wire:model="fecha_vencimiento"
                                    >
                                    @error('fecha_vencimiento')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
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
                                {{ $isEditing ? 'Actualizar Entrada' : 'Registrar Entrada' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
