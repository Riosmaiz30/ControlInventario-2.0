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
                                    Selecciona los lotes por su número, nombre de producto o SKU.
                                    El stock se descuenta únicamente del <strong>lote elegido</strong>.
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

                            <div class="mb-3">
                                <label for="lote_busqueda" class="form-label">
                                    <i class="bi bi-search me-1"></i> Buscar lote
                                </label>
                                <input
                                    id="lote_busqueda"
                                    type="search"
                                    class="form-control @error('loteBusqueda') is-invalid @enderror"
                                    placeholder="Número de lote, nombre de producto o SKU..."
                                    wire:model.live.debounce.300ms="loteBusqueda"
                                    wire:keydown.enter.prevent="agregarPrimerLote"
                                    autocomplete="off"
                                >
                                @error('loteBusqueda')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    Escribe o escanea el lote, el producto o el SKU y presiona
                                    <strong>Enter</strong> para agregarlo con su disponibilidad.
                                    Luego escribe la cantidad y presiona <strong>Enter</strong>
                                    para regresar al buscador.
                                </div>
                            </div>

                            @if ($lotesDisponibles->isNotEmpty())
                                <div class="list-group mb-3">
                                    @foreach ($lotesDisponibles as $lote)
                                        <button
                                            type="button"
                                            class="list-group-item list-group-item-action d-flex flex-wrap justify-content-between align-items-center gap-2"
                                            wire:click="agregarLote({{ $lote->id }})"
                                            wire:key="resultado-lote-{{ $lote->id }}"
                                        >
                                            <span>
                                                <span class="fw-semibold">
                                                    {{ $lote->producto?->nombre ?? 'Producto eliminado' }}
                                                </span>
                                                @if ($lote->producto?->sku)
                                                    <span class="badge text-bg-secondary ms-1">{{ $lote->producto->sku }}</span>
                                                @endif
                                                <div class="small mt-1">
                                                    <span class="badge text-bg-light border">
                                                        Lote {{ $lote->numero_lote }}
                                                    </span>
                                                    <span class="text-muted ms-1">
                                                        Vence: {{ $lote->fecha_vencimiento?->format('d/m/Y') ?? 'sin fecha' }}
                                                    </span>
                                                </div>
                                            </span>

                                            <span class="d-flex align-items-center gap-2">
                                                <span class="badge text-bg-success">
                                                    {{ number_format((float) $lote->stock_actual, 2, ',', '.') }}
                                                    {{ $lote->producto?->unidad_medida }}
                                                </span>
                                                <i class="bi bi-plus-circle"></i>
                                            </span>
                                        </button>
                                    @endforeach
                                </div>
                            @else
                                <div class="alert alert-light border text-muted small mb-3" role="status">
                                    <i class="bi bi-info-circle me-1"></i>
                                    {{ filled($loteBusqueda)
                                        ? 'No hay lotes con stock que coincidan con la búsqueda.'
                                        : 'No hay más lotes con stock disponible para agregar.' }}
                                </div>
                            @endif

                            <h6 class="mb-2">
                                <i class="bi bi-box-seam me-1"></i> Lotes en el despacho
                            </h6>

                            @if (empty($items))
                                <div class="alert alert-light border text-center text-muted mb-0">
                                    <i class="bi bi-inbox fs-4 d-block mb-1"></i>
                                    Busca un lote arriba para agregarlo al despacho.
                                </div>
                            @else
                                <div class="table-responsive border rounded">
                                    <table class="table align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Producto / Lote</th>
                                                <th style="width: 130px;">Vencimiento</th>
                                                <th style="width: 140px;">Disponible</th>
                                                <th style="width: 170px;">Cantidad</th>
                                                <th class="text-center" style="width: 60px;"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($items as $index => $item)
                                                @php
                                                    $loteSeleccionado = $lotesSeleccionados->get((int) ($item['lote_id'] ?? 0));
                                                    $productoSeleccionado = $loteSeleccionado?->producto;
                                                    $stockDisponible = (float) ($loteSeleccionado?->stock_actual ?? 0);
                                                    $cantidadIngresada = (float) ($item['cantidad'] ?: 0);
                                                @endphp

                                                <tr wire:key="item-{{ $item['lote_id'] }}">
                                                    <td>
                                                        <div class="fw-semibold">
                                                            {{ $productoSeleccionado?->nombre ?? 'Producto no disponible' }}
                                                        </div>
                                                        <div class="small mt-1">
                                                            <span class="badge text-bg-secondary">
                                                                Lote {{ $loteSeleccionado?->numero_lote ?? '—' }}
                                                            </span>
                                                            @if ($productoSeleccionado?->sku)
                                                                <span class="text-muted ms-1">{{ $productoSeleccionado->sku }}</span>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td class="text-nowrap">
                                                        {{ $loteSeleccionado?->fecha_vencimiento?->format('d/m/Y') ?? 'Sin fecha' }}
                                                    </td>
                                                    <td>
                                                        <span class="badge text-bg-{{ $stockDisponible > 0 ? 'success' : 'secondary' }}">
                                                            {{ number_format($stockDisponible, 2, ',', '.') }}
                                                            {{ $productoSeleccionado?->unidad_medida }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <input
                                                            id="cantidad-{{ $index }}"
                                                            type="number"
                                                            step="0.01"
                                                            min="0.01"
                                                            class="form-control @error('items.'.$index.'.cantidad') is-invalid @enderror"
                                                            wire:model.live.debounce.500ms="items.{{ $index }}.cantidad"
                                                            wire:keydown.enter.prevent="enfocarBuscador"
                                                            placeholder="0.00"
                                                        >
                                                        @error('items.'.$index.'.cantidad')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                        @if ($cantidadIngresada > $stockDisponible)
                                                            <div class="text-danger small mt-1">
                                                                Supera el stock del lote.
                                                            </div>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">
                                                        <button
                                                            type="button"
                                                            class="btn btn-outline-danger btn-sm"
                                                            wire:click="quitarItem({{ $index }})"
                                                            title="Quitar lote"
                                                        >
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr class="table-light">
                                                <td colspan="3" class="text-end fw-semibold">Total de unidades:</td>
                                                <td class="fw-bold">
                                                    {{ number_format(collect($items)->sum(fn ($item) => (float) ($item['cantidad'] ?? 0)), 2, ',', '.') }}
                                                </td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            @endif

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
                        </div>

                        <div class="modal-footer">
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
        </script>
    @endscript
</div>
