<div>
    {{-- Alertas SweetAlert2 --}}
    @include('partials.swal-flash')

    {{-- Tarjeta principal --}}
    <div class="card card-outline card-primary">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h3 class="card-title mb-0">
                <i class="bi bi-paper-plane me-1"></i> Despachos a Sedes
            </h3>

            <div class="d-flex flex-wrap align-items-center gap-2">
                {{-- Buscador --}}
                <div class="input-group input-group-sm" style="width: 280px;">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input
                        type="search"
                        class="form-control"
                        placeholder="Buscar por Nº, sede o producto..."
                        wire:model.live.debounce.300ms="search"
                        aria-label="Buscar despachos"
                    >
                </div>

                {{-- Nuevo despacho --}}
                <button type="button" class="btn btn-primary btn-sm" wire:click="crear">
                    <i class="bi bi-plus-lg me-1"></i> Nuevo Despacho
                </button>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nº de Despacho</th>
                            <th>Fecha</th>
                            <th>Sede de Destino</th>
                            <th class="text-center">Productos / Unidades</th>
                            <th>Despachado / Recibido</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center" style="width: 110px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody wire:loading.class="opacity-50">
                        @forelse ($despachos as $despacho)
                            <tr wire:key="despacho-{{ $despacho->id }}">
                                <td>
                                    <span class="badge text-bg-secondary">{{ $despacho->numero }}</span>
                                </td>
                                <td class="text-nowrap">
                                    {{ $despacho->fecha_despacho?->format('d/m/Y') ?? '—' }}
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $despacho->sede?->nombre ?? 'Sede eliminada' }}</div>
                                    @if ($despacho->consumo_interno)
                                        <span class="badge text-bg-warning mt-1">
                                            <i class="bi bi-house-door me-1"></i>Consumo interno
                                        </span>
                                    @elseif ($despacho->sede)
                                        <span class="badge text-bg-{{ $despacho->sede->tipo === 'principal' ? 'primary' : 'info' }} mt-1">
                                            {{ $despacho->sede->tipo === 'principal' ? 'Principal' : 'Punto de consumo' }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="fw-semibold">{{ $despacho->detalles_count }}</span>
                                    <span class="text-muted">/</span>
                                    <span class="fw-semibold">
                                        {{ number_format((float) $despacho->total_unidades, 2, ',', '.') }}
                                    </span>
                                </td>
                                <td>
                                    <div class="small text-nowrap" title="Jefe de Almacén que despacha">
                                        <i class="bi bi-box-arrow-up-right text-muted me-1"></i>
                                        <span class="fw-semibold">{{ $despacho->despachado_por_nombre ?? $despacho->despachadoPor?->nombres ?? '—' }}</span>
                                    </div>
                                    <div class="small text-muted text-nowrap" title="Jefe de Centro que recibe">
                                        <i class="bi bi-box-arrow-in-down me-1"></i>
                                        <span class="fw-semibold">{{ $despacho->recibido_por_nombre ?? $despacho->recibidoPor?->nombres ?? '—' }}</span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if ($despacho->estado === 'anulado')
                                        <span class="badge text-bg-danger">Anulado</span>
                                    @else
                                        <span class="badge text-bg-success">Registrado</span>
                                    @endif
                                </td>
                                <td class="text-center text-nowrap">
                                    <button
                                        type="button"
                                        class="btn btn-info btn-sm"
                                        wire:click="verDetalle({{ $despacho->id }})"
                                        title="Ver detalle"
                                    >
                                        <i class="bi bi-eye"></i>
                                    </button>

                                    <a
                                        href="{{ route('despachos.nota-entrega', $despacho) }}"
                                        class="btn btn-secondary btn-sm"
                                        target="_blank"
                                        title="Descargar nota de entrega"
                                    >
                                        <i class="bi bi-file-earmark-pdf"></i>
                                    </a>

                                    @if ($despacho->estado !== 'anulado')
                                        <button
                                            type="button"
                                            class="btn btn-danger btn-sm"
                                            data-confirm-delete
                                            data-confirm-id="{{ $despacho->id }}"
                                            data-confirm-method="anular"
                                            data-confirm-entity="este despacho"
                                            data-confirm-title="¿Anular este despacho?"
                                            data-confirm-text="Las cantidades descontadas serán devueltas a sus lotes de origen. El despacho quedará registrado como anulado."
                                            data-confirm-button="Sí, anular"
                                            title="Anular despacho"
                                        >
                                            <i class="bi bi-x-octagon"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                    No se encontraron despachos registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($despachos->hasPages())
            <div class="card-footer">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <small class="text-muted">
                        Mostrando {{ $despachos->firstItem() ?? 0 }}–{{ $despachos->lastItem() ?? 0 }}
                        de {{ $despachos->total() }} despachos
                    </small>
                    {{ $despachos->links('pagination::bootstrap-5') }}
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
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <form wire:submit="guardar">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="bi bi-paper-plane me-1"></i> Nuevo Despacho
                            </h5>
                            <button type="button" class="btn-close" wire:click="cerrarModal" aria-label="Cerrar"></button>
                        </div>

                        <div class="modal-body">
                            <div class="alert alert-info d-flex align-items-start gap-2 mb-3" role="alert">
                                <i class="bi bi-info-circle-fill mt-1"></i>
                                <span>
                                    Escribe el número de lote, nombre de producto o SKU en el buscador
                                    y presiona <strong>Enter</strong> para agregar el primer resultado
                                    al carrito. El stock se descuenta únicamente del <strong>lote elegido</strong>.
                                    Los despachos a la Sede Principal se registran como
                                    <strong>consumo interno</strong>.
                                </span>
                            </div>

                            @error('items')
                                <div class="alert alert-danger py-2" role="alert">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $message }}
                                </div>
                            @enderror

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="sede_id" class="form-label">
                                        Sede de destino <span class="text-danger">*</span>
                                    </label>
                                    <select
                                        id="sede_id"
                                        class="form-select @error('sede_id') is-invalid @enderror"
                                        wire:model.live="sede_id"
                                    >
                                        <option value="">Seleccione una sede</option>
                                        @foreach ($sedes as $sede)
                                            <option value="{{ $sede->id }}">
                                                {{ $sede->nombre }} — {{ $sede->tipo === 'principal' ? 'Principal' : 'Punto de consumo' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('sede_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-3">
                                    <label for="fecha_despacho" class="form-label">
                                        Fecha de despacho <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        id="fecha_despacho"
                                        type="date"
                                        class="form-control @error('fecha_despacho') is-invalid @enderror"
                                        wire:model="fecha_despacho"
                                    >
                                    @error('fecha_despacho')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-3 d-flex align-items-end">
                                    <div class="text-muted small">
                                        <i class="bi bi-upc-scan me-1"></i>
                                        El número se genera al guardar.
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">
                                        <i class="bi bi-box-arrow-up-right me-1"></i>
                                        Despachado por (Jefe de Almacén)
                                    </label>
                                    <div class="border rounded bg-light px-3 py-2">
                                        @if ($jefeAlmacen)
                                            <i class="bi bi-person-check-fill text-success me-1"></i>
                                            <span class="fw-semibold">
                                                {{ trim($jefeAlmacen->nombres.' '.$jefeAlmacen->apellidos) }}
                                            </span>
                                            <span class="text-muted small">— {{ $jefeAlmacen->cargo }}</span>
                                        @else
                                            <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>
                                            <span class="text-danger small">
                                                No configurado. Registra un empleado activo con el cargo «Jefe de Almacén».
                                            </span>
                                        @endif
                                    </div>
                                    @error('despachado_por')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">
                                        <i class="bi bi-box-arrow-in-down me-1"></i>
                                        Recibido por (Jefe de Centro)
                                    </label>
                                    <div class="border rounded bg-light px-3 py-2">
                                        @if (! $sedeSeleccionada)
                                            <span class="text-muted small">
                                                Selecciona una sede para asignar al Jefe de Centro.
                                            </span>
                                        @elseif ($jefeCentro)
                                            <i class="bi bi-person-check-fill text-success me-1"></i>
                                            <span class="fw-semibold">
                                                {{ trim($jefeCentro->nombres.' '.$jefeCentro->apellidos) }}
                                            </span>
                                            <span class="text-muted small">— {{ $jefeCentro->cargo }}</span>
                                        @else
                                            <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>
                                            <span class="text-danger small">
                                                La sede «{{ $sedeSeleccionada->nombre }}» no tiene un responsable
                                                con el cargo «Jefe de Centro». Asígnalo desde el módulo de Sedes.
                                            </span>
                                        @endif
                                    </div>
                                    @error('recibido_por')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            @if ($sedeSeleccionada?->tipo === 'principal')
                                <div class="alert alert-warning d-flex align-items-start gap-2 mb-3" role="alert">
                                    <i class="bi bi-house-door-fill mt-1"></i>
                                    <span>
                                        La sede seleccionada es la <strong>Sede Principal</strong>. Este despacho
                                        se registrará como <strong>consumo interno</strong>.
                                    </span>
                                </div>
                            @endif

                            <div class="card card-outline card-primary mb-3">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="bi bi-cart3 me-1"></i> Productos a entregar
                                    </h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="row g-0">
                                        {{-- 3 columnas: buscador de lotes --}}
                                        <div class="col-md-3 border-end bg-light">
                                            <div class="p-3">
                                                <label for="lote_busqueda" class="form-label small fw-semibold">
                                                    <i class="bi bi-search me-1"></i> Buscar lote
                                                </label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                                    <input
                                                        id="lote_busqueda"
                                                        type="search"
                                                        class="form-control @error('loteBusqueda') is-invalid @enderror"
                                                        placeholder="Nº lote, producto o SKU..."
                                                        wire:model.live.debounce.300ms="loteBusqueda"
                                                        wire:keydown.enter.prevent="agregarPrimerLote"
                                                        autocomplete="off"
                                                    >
                                                </div>
                                                @error('loteBusqueda')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                                <div class="form-text small">
                                                    Presiona <strong>Enter</strong> para agregar el primero.
                                                </div>
                                            </div>
                                        </div>

                                        {{-- 9 columnas: carrito --}}
                                        <div class="col-md-9">
                                            <div style="max-height: 400px; overflow-y: auto;">
                                                @if (empty($items))
                                                    <div class="alert alert-light border text-center text-muted m-3 mb-0 py-5">
                                                        <i class="bi bi-cart-plus fs-1 d-block mb-2"></i>
                                                        Aún no hay productos en el carrito.
                                                        <div class="small mt-1">Busca un lote a la izquierda y agrégalo aquí.</div>
                                                    </div>
                                                @else
                                                    <div class="list-group list-group-flush">
                                                        @foreach ($items as $index => $item)
                                                            @php
                                                                $loteSeleccionado = $lotesSeleccionados->get((int) ($item['lote_id'] ?? 0));
                                                                $productoSeleccionado = $loteSeleccionado?->producto;
                                                                $stockDisponible = (float) ($loteSeleccionado?->stock_actual ?? 0);
                                                                $stockInicial = (float) ($loteSeleccionado?->stock_inicial ?? 0);
                                                                $stockMinimo = (float) ($productoSeleccionado?->stock_minimo_alerta ?? 0);
                                                                $cantidadIngresada = (float) ($item['cantidad'] ?: 0);

                                                                $colorStock = 'success';
                                                                if ($stockDisponible <= $stockMinimo) {
                                                                    $colorStock = 'danger';
                                                                } elseif ($stockDisponible <= ($stockInicial / 2)) {
                                                                    $colorStock = 'warning';
                                                                }
                                                            @endphp

                                                            <div class="list-group-item" wire:key="item-{{ $item['lote_id'] }}">
                                                                <div class="row align-items-center g-2">
                                                                    <div class="col-md-5">
                                                                        <div class="fw-semibold text-truncate">
                                                                            {{ $productoSeleccionado?->nombre ?? 'Producto no disponible' }}
                                                                        </div>
                                                                        <div class="small text-muted">
                                                                            Lote {{ $loteSeleccionado?->numero_lote ?? '—' }}
                                                                            @if ($productoSeleccionado?->sku)
                                                                                · {{ $productoSeleccionado->sku }}
                                                                            @endif
                                                                        </div>
                                                                        <div class="small text-muted">
                                                                            Vence {{ $loteSeleccionado?->fecha_vencimiento?->format('d/m/Y') ?? '—' }}
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-3">
                                                                        <span class="badge text-bg-{{ $colorStock }}">
                                                                            <i class="bi bi-circle-fill me-1" style="font-size: .5rem;"></i>
                                                                            Disp. {{ number_format($stockDisponible, 2, ',', '.') }}
                                                                            {{ $productoSeleccionado?->unidad_medida }}
                                                                        </span>
                                                                    </div>
                                                                    <div class="col-md-3">
                                                                        <input
                                                                            id="cantidad-{{ $index }}"
                                                                            type="number"
                                                                            step="0.01"
                                                                            min="0.01"
                                                                            class="form-control form-control-sm @error('items.'.$index.'.cantidad') is-invalid @enderror"
                                                                            wire:model.live.debounce.500ms="items.{{ $index }}.cantidad"
                                                                            wire:keydown.enter.prevent="enfocarBuscador"
                                                                            placeholder="Cantidad"
                                                                        >
                                                                        @error('items.'.$index.'.cantidad')
                                                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                                                        @enderror
                                                                        @if ($cantidadIngresada > $stockDisponible)
                                                                            <div class="text-danger small mt-1">
                                                                                <i class="bi bi-exclamation-triangle me-1"></i>Supera el stock del lote.
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                    <div class="col-md-1 text-end">
                                                                        <button
                                                                            type="button"
                                                                            class="btn btn-outline-danger btn-sm py-0 px-2"
                                                                            wire:click="quitarItem({{ $index }})"
                                                                            title="Quitar del carrito"
                                                                        >
                                                                            <i class="bi bi-trash"></i>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <i class="bi bi-box-seam text-primary"></i>
                                        <span class="fw-semibold">Productos en el carrito:</span>
                                        <strong>{{ count($items) }}</strong>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach ($items as $item)
                                            @php
                                                $loteResumen = $lotesSeleccionados->get((int) ($item['lote_id'] ?? 0));
                                            @endphp
                                            <span class="badge text-bg-light border fw-normal">
                                                {{ $loteResumen?->producto?->nombre ?? 'Producto' }}
                                                <span class="text-muted">({{ $loteResumen?->numero_lote ?? '—' }}):</span>
                                                <strong>{{ number_format((float) ($item['cantidad'] ?? 0), 2, ',', '.') }}</strong>
                                                {{ $loteResumen?->producto?->unidad_medida }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3">
                                <label for="observaciones" class="form-label">Observaciones</label>
                                <textarea
                                    id="observaciones"
                                    rows="2"
                                    class="form-control @error('observaciones') is-invalid @enderror"
                                    wire:model="observaciones"
                                    placeholder="Opcional: responsable que recibe, transporte, notas..."
                                ></textarea>
                                @error('observaciones')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex justify-content-end gap-2 mt-3 pt-3 border-top bg-white position-sticky bottom-0">
                                <button type="button" class="btn btn-secondary" wire:click="cerrarModal">
                                    <i class="bi bi-x-lg me-1"></i> Cancelar
                                </button>

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
                                    Registrar Despacho
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Detalle del despacho (modal) --}}
    @if ($detalle)
        <div
            class="modal fade show d-block"
            tabindex="-1"
            role="dialog"
            aria-modal="true"
            style="background-color: rgba(0, 0, 0, .5);"
            wire:keydown.escape.window="cerrarDetalle"
            wire:click.self="cerrarDetalle"
        >
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-file-earmark-text me-1"></i>
                            Despacho {{ $detalle->numero }}
                        </h5>
                        <button type="button" class="btn-close" wire:click="cerrarDetalle" aria-label="Cerrar"></button>
                    </div>

                    <div class="modal-body">
                        @if ($detalle->estado === 'anulado')
                            <div class="alert alert-warning d-flex align-items-start gap-2" role="alert">
                                <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                                <span>
                                    Este despacho fue <strong>anulado</strong> y las cantidades
                                    fueron devueltas a sus lotes de origen.
                                </span>
                            </div>
                        @endif

                        @php
                            $nombreDespachador = $detalle->despachado_por_nombre
                                ?: ($detalle->despachadoPor
                                    ? trim($detalle->despachadoPor->nombres.' '.$detalle->despachadoPor->apellidos)
                                    : null);

                            $nombreReceptor = $detalle->recibido_por_nombre
                                ?: ($detalle->recibidoPor
                                    ? trim($detalle->recibidoPor->nombres.' '.$detalle->recibidoPor->apellidos)
                                    : null);
                        @endphp

                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <div class="text-muted small">Sede de destino</div>
                                <div class="fw-semibold">{{ $detalle->sede?->nombre ?? 'Sede eliminada' }}</div>
                                @if ($detalle->consumo_interno)
                                    <span class="badge text-bg-warning mt-1">
                                        <i class="bi bi-house-door me-1"></i>Consumo interno
                                    </span>
                                @endif
                            </div>

                            <div class="col-sm-6">
                                <div class="text-muted small">Fecha de despacho</div>
                                <div class="fw-semibold">{{ $detalle->fecha_despacho?->format('d/m/Y') ?? '—' }}</div>
                            </div>

                            <div class="col-sm-6">
                                <div class="text-muted small">Despachado por (Jefe de Almacén)</div>
                                <div class="fw-semibold">{{ $nombreDespachador ?? '—' }}</div>
                            </div>

                            <div class="col-sm-6">
                                <div class="text-muted small">Recibido por (Jefe de Centro)</div>
                                <div class="fw-semibold">{{ $nombreReceptor ?? '—' }}</div>
                            </div>

                            <div class="col-sm-6">
                                <div class="text-muted small">Registrado por</div>
                                <div class="fw-semibold">{{ $detalle->user?->name ?? 'Sistema' }}</div>
                            </div>

                            <div class="col-sm-6">
                                <div class="text-muted small">Estado</div>
                                @if ($detalle->estado === 'anulado')
                                    <span class="badge text-bg-danger">Anulado</span>
                                @else
                                    <span class="badge text-bg-success">Registrado</span>
                                @endif
                            </div>
                        </div>

                        @if (filled($detalle->observaciones))
                            <div class="mb-3">
                                <div class="text-muted small">Observaciones</div>
                                <div>{{ $detalle->observaciones }}</div>
                            </div>
                        @endif

                        <div class="table-responsive border rounded">
                            <table class="table table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Producto</th>
                                        <th>Lote de origen</th>
                                        <th>Vencimiento</th>
                                        <th class="text-end">Cantidad</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($detalle->detalles as $linea)
                                        <tr wire:key="detalle-{{ $linea->id }}">
                                            <td>{{ $linea->producto_nombre }}</td>
                                            <td>
                                                <span class="badge text-bg-secondary">{{ $linea->numero_lote }}</span>
                                            </td>
                                            <td>
                                                {{ $linea->lote?->fecha_vencimiento?->format('d/m/Y') ?? '—' }}
                                            </td>
                                            <td class="text-end fw-semibold">
                                                {{ number_format((float) $linea->cantidad, 2, ',', '.') }}
                                                {{ $linea->producto?->unidad_medida }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">
                                                Este despacho no tiene líneas registradas.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr class="table-light">
                                        <td colspan="3" class="text-end fw-semibold">Total de unidades:</td>
                                        <td class="text-end fw-bold">
                                            {{ number_format((float) $detalle->detalles->sum('cantidad'), 2, ',', '.') }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="cerrarDetalle">
                            <i class="bi bi-x-lg me-1"></i> Cerrar
                        </button>
                        <a
                            href="{{ route('despachos.nota-entrega', $detalle) }}"
                            class="btn btn-primary"
                            target="_blank"
                        >
                            <i class="bi bi-file-earmark-pdf me-1"></i> Nota de entrega
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @script
        <script>
            $wire.on('enfocar-buscador', () => {
                setTimeout(() => document.getElementById('lote_busqueda')?.focus(), 60);
            });

            $wire.on('enfocar-cantidad', (event) => {
                setTimeout(() => document.getElementById(`cantidad-${event.index}`)?.focus(), 60);
            });

            $wire.on('descargar-nota-entrega', (event) => {
                const enlace = document.createElement('a');
                enlace.href = event.url;
                enlace.download = '';
                document.body.appendChild(enlace);
                enlace.click();
                enlace.remove();
            });
        </script>
    @endscript
</div>
