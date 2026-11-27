<?php

namespace App\Livewire;

use App\Models\Auditoria;
use App\Models\Despacho;
use App\Models\DespachoDetalle;
use App\Models\Empleado;
use App\Models\Lote;
use App\Models\ProgramacionDespacho;
use App\Models\ProgramacionDespachoDetalle;
use App\Models\Sede;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class GestionDespachos extends Component
{
    use WithPagination;

    /** Texto de búsqueda por número, sede o producto. */
    public string $search = '';

    /** Propiedades del formulario de despacho. */
    public $sede_id = '';

    public $fecha_despacho = '';

    public $observaciones = '';

    /** Líneas del despacho: cada una referencia un lote y una cantidad. */
    public array $items = [];

    /** Texto del buscador de lotes del formulario. */
    public string $loteBusqueda = '';

    /** Controla la visibilidad del formulario. */
    public bool $mostrarModal = false;

    /** Despacho seleccionado para ver su detalle. */
    public $detalleId = null;

    /** Controla la visibilidad del formulario de programación. */
    public bool $mostrarModalProgramacion = false;

    /** Programación que se está editando, si corresponde. */
    public $programacionId = null;

    /** Despacho origen de la programación. */
    public $programacionDespachoId = null;

    /** Campos del formulario de programación. */
    public $programacionSedeId = '';

    public $programacionCadaDias = 7;

    public $programacionFechaInicio = '';

    public $programacionObservaciones = '';

    public array $programacionDetalles = [];

    /**
     * Carga el borrador solicitado desde la campana de notificaciones.
     */
    public function mount(): void
    {
        $programacionId = (int) request()->query('programacion', 0);

        if ($programacionId > 0) {
            $this->crearDesdeProgramacion($programacionId);
        }
    }

    /**
     * Reglas de validación para el registro de despachos.
     */
    protected function rules(): array
    {
        return [
            'sede_id' => ['required', 'integer', 'exists:sedes,id'],
            'fecha_despacho' => ['required', 'date'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.lote_id' => ['required', 'integer', 'distinct', 'exists:lotes,id'],
            'items.*.cantidad' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
        ];
    }

    /**
     * Mensajes de validación en español.
     */
    protected function messages(): array
    {
        return [
            'sede_id.required' => 'Debe seleccionar una sede de destino.',
            'sede_id.integer' => 'La sede seleccionada no es válida.',
            'sede_id.exists' => 'La sede seleccionada no existe.',
            'fecha_despacho.required' => 'La fecha de despacho es obligatoria.',
            'fecha_despacho.date' => 'La fecha de despacho no es válida.',
            'observaciones.max' => 'Las observaciones no pueden superar los 1000 caracteres.',
            'items.required' => 'Debe agregar al menos un lote al despacho.',
            'items.min' => 'Debe agregar al menos un lote al despacho.',
            'items.*.lote_id.required' => 'Debe seleccionar un lote en cada línea.',
            'items.*.lote_id.integer' => 'El lote seleccionado no es válido.',
            'items.*.lote_id.exists' => 'El lote seleccionado no existe.',
            'items.*.lote_id.distinct' => 'No se puede repetir el mismo lote en el despacho.',
            'items.*.cantidad.required' => 'La cantidad es obligatoria.',
            'items.*.cantidad.numeric' => 'La cantidad debe ser un número.',
            'items.*.cantidad.gt' => 'La cantidad debe ser mayor a 0.',
            'items.*.cantidad.max' => 'La cantidad es demasiado grande.',
        ];
    }

    /**
     * Nombres legibles de los campos.
     */
    protected function validationAttributes(): array
    {
        return [
            'sede_id' => 'sede',
            'fecha_despacho' => 'fecha de despacho',
            'observaciones' => 'observaciones',
            'items' => 'lotes',
            'items.*.lote_id' => 'lote',
            'items.*.cantidad' => 'cantidad',
        ];
    }

    /**
     * Reglas de validación para programar envíos periódicos.
     */
    protected function rulesProgramacion(): array
    {
        return [
            'programacionSedeId' => ['required', 'integer', 'exists:sedes,id'],
            'programacionCadaDias' => ['required', 'integer', 'min:1', 'max:365'],
            'programacionFechaInicio' => ['required', 'date'],
            'programacionObservaciones' => ['nullable', 'string', 'max:1000'],
            'programacionDetalles' => ['required', 'array', 'min:1'],
            'programacionDetalles.*.producto_id' => ['nullable', 'integer', 'exists:productos,id'],
            'programacionDetalles.*.producto_nombre' => ['required', 'string', 'max:255'],
            'programacionDetalles.*.cantidad' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
        ];
    }

    /**
     * Mensajes de validación de la programación.
     */
    protected function messagesProgramacion(): array
    {
        return [
            'programacionSedeId.required' => 'Debe seleccionar una sede de destino.',
            'programacionCadaDias.required' => 'Debe indicar cada cuántos días se enviará.',
            'programacionCadaDias.integer' => 'El periodo debe ser un número entero de días.',
            'programacionCadaDias.min' => 'El periodo mínimo es de 1 día.',
            'programacionCadaDias.max' => 'El periodo máximo es de 365 días.',
            'programacionFechaInicio.required' => 'La fecha de inicio es obligatoria.',
            'programacionFechaInicio.date' => 'La fecha de inicio no es válida.',
            'programacionObservaciones.max' => 'Las observaciones no pueden superar los 1000 caracteres.',
            'programacionDetalles.required' => 'La programación debe incluir al menos un producto.',
            'programacionDetalles.min' => 'La programación debe incluir al menos un producto.',
            'programacionDetalles.*.producto_nombre.required' => 'El nombre del producto es obligatorio.',
            'programacionDetalles.*.cantidad.required' => 'La cantidad es obligatoria.',
            'programacionDetalles.*.cantidad.numeric' => 'La cantidad debe ser un número.',
            'programacionDetalles.*.cantidad.gt' => 'La cantidad debe ser mayor a 0.',
            'programacionDetalles.*.cantidad.max' => 'La cantidad es demasiado grande.',
        ];
    }

    /**
     * Nombres legibles de los campos de la programación.
     */
    protected function validationAttributesProgramacion(): array
    {
        return [
            'programacionSedeId' => 'sede',
            'programacionCadaDias' => 'periodo en días',
            'programacionFechaInicio' => 'fecha de inicio',
            'programacionDetalles' => 'productos',
            'programacionDetalles.*.cantidad' => 'cantidad',
        ];
    }

    /**
     * Reinicia la paginación cuando cambia la búsqueda.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Abre el formulario para registrar un despacho.
     */
    public function crear(): void
    {
        Gate::authorize('preparar_despachos');

        $this->resetValidation();
        $this->resetForm();
        $this->mostrarModal = true;
        $this->dispatch('enfocar-buscador');
    }

    /**
     * Abre el formulario para programar envíos desde un despacho existente.
     */
    public function programar($despachoId): void
    {
        Gate::authorize('programar_despachos');

        $despacho = Despacho::query()->with('detalles')->find($despachoId);

        if (! $despacho) {
            session()->flash('error', 'El despacho solicitado no existe.');

            return;
        }

        if ($despacho->estado === Despacho::ESTADO_ANULADO) {
            session()->flash('warning', 'No se puede programar un despacho anulado.');

            return;
        }

        $existente = ProgramacionDespacho::query()
            ->where('despacho_origen_id', $despacho->id)
            ->first();

        if ($existente) {
            $this->editarProgramacion($existente->id);
            session()->flash('info', 'Este despacho ya tenía una programación; puedes editarla aquí.');

            return;
        }

        $this->resetValidation();
        $this->resetFormProgramacion();
        $this->programacionDespachoId = $despacho->id;
        $this->programacionSedeId = $despacho->sede_id;
        $this->programacionObservaciones = $despacho->observaciones ?? '';
        $this->programacionDetalles = $despacho->detalles->map(fn ($detalle) => [
            'producto_id' => $detalle->producto_id,
            'producto_nombre' => $detalle->producto_nombre,
            'cantidad' => (float) $detalle->cantidad,
        ])->values()->all();
        $this->mostrarModalProgramacion = true;
    }

    /**
     * Carga una programación existente en el formulario.
     */
    public function editarProgramacion($id): void
    {
        Gate::authorize('programar_despachos');

        $programacion = ProgramacionDespacho::query()->with('detalles')->find($id);

        if (! $programacion) {
            session()->flash('error', 'La programación solicitada no existe.');

            return;
        }

        $this->resetValidation();
        $this->resetFormProgramacion();
        $this->programacionId = $programacion->id;
        $this->programacionDespachoId = $programacion->despacho_origen_id;
        $this->programacionSedeId = $programacion->sede_id;
        $this->programacionCadaDias = $programacion->cada_dias;
        $this->programacionFechaInicio = $programacion->fecha_inicio?->format('Y-m-d') ?? '';
        $this->programacionObservaciones = $programacion->observaciones ?? '';
        $this->programacionDetalles = $programacion->detalles->map(fn ($detalle) => [
            'producto_id' => $detalle->producto_id,
            'producto_nombre' => $detalle->producto_nombre,
            'cantidad' => (float) $detalle->cantidad,
        ])->values()->all();
        $this->mostrarModalProgramacion = true;
    }

    /**
     * Valida y guarda la programación de envíos.
     */
    public function guardarProgramacion(): void
    {
        Gate::authorize('programar_despachos');

        $this->validate($this->rulesProgramacion(), $this->messagesProgramacion(), $this->validationAttributesProgramacion());

        $fechaInicio = Carbon::parse($this->programacionFechaInicio);
        $datos = [
            'despacho_origen_id' => $this->programacionDespachoId,
            'sede_id' => $this->programacionSedeId,
            'cada_dias' => (int) $this->programacionCadaDias,
            'fecha_inicio' => $fechaInicio->toDateString(),
            'observaciones' => filled($this->programacionObservaciones) ? $this->programacionObservaciones : null,
            'proxima_fecha' => $fechaInicio->copy()->startOfDay(),
        ];

        $programacion = null;
        $anteriores = null;
        $accion = 'programacion_creada';

        DB::transaction(function () use (&$programacion, &$anteriores, &$accion, $datos, $fechaInicio) {
            if ($this->programacionId) {
                $programacion = ProgramacionDespacho::query()->findOrFail($this->programacionId);
                $anteriores = $this->datosAuditoriaProgramacion($programacion);
                $accion = 'programacion_actualizada';

                $mismoPeriodo = (int) $programacion->cada_dias === (int) $this->programacionCadaDias
                    && $programacion->fecha_inicio?->toDateString() === $fechaInicio->toDateString();

                if ($mismoPeriodo) {
                    unset($datos['proxima_fecha']);
                }

                $programacion->update($datos);
                $programacion->detalles()->delete();
            } else {
                $existe = ProgramacionDespacho::query()
                    ->where('despacho_origen_id', $this->programacionDespachoId)
                    ->exists();

                if ($existe) {
                    throw ValidationException::withMessages([
                        'programacionDespachoId' => 'Este despacho ya tiene una programación activa.',
                    ]);
                }

                $programacion = ProgramacionDespacho::create($datos + ['user_id' => auth()->id()]);
            }

            foreach ($this->programacionDetalles as $detalle) {
                ProgramacionDespachoDetalle::create([
                    'programacion_despacho_id' => $programacion->id,
                    'producto_id' => $detalle['producto_id'] ?? null,
                    'producto_nombre' => $detalle['producto_nombre'],
                    'cantidad' => (float) $detalle['cantidad'],
                ]);
            }

            $programacion->load(['detalles', 'sede']);

            Auditoria::create([
                'user_id' => auth()->id(),
                'accion' => $accion,
                'modelo' => 'programacion_despacho',
                'modelo_id' => $programacion->id,
                'descripcion' => "Programación de envío a {$programacion->sede?->nombre} guardada cada {$programacion->cada_dias} días",
                'datos_anteriores' => $anteriores,
                'datos_nuevos' => $this->datosAuditoriaProgramacion($programacion),
            ]);
        });

        $this->cerrarModalProgramacion();

        session()->flash('success', 'Envío programado correctamente.');
    }

    /**
     * Pausa una programación existente.
     */
    public function pausarProgramacion($id): void
    {
        $this->cambiarEstadoProgramacion((int) $id, false, 'programacion_pausada', 'Programación pausada correctamente.');
    }

    /**
     * Reactiva una programación pausada.
     */
    public function activarProgramacion($id): void
    {
        $this->cambiarEstadoProgramacion((int) $id, true, 'programacion_activada', 'Programación activada correctamente.');
    }

    /**
     * Elimina una programación y sus productos.
     */
    public function eliminarProgramacion($id): void
    {
        Gate::authorize('programar_despachos');

        $programacion = ProgramacionDespacho::query()->find($id);

        if (! $programacion) {
            session()->flash('error', 'La programación solicitada no existe.');

            return;
        }

        $anteriores = $this->datosAuditoriaProgramacion($programacion->load('detalles'));

        DB::transaction(function () use ($programacion, $anteriores) {
            $programacion->delete();

            Auditoria::create([
                'user_id' => auth()->id(),
                'accion' => 'programacion_eliminada',
                'modelo' => 'programacion_despacho',
                'modelo_id' => $programacion->id,
                'descripcion' => 'Programación de envío eliminada',
                'datos_anteriores' => $anteriores,
                'datos_nuevos' => null,
            ]);
        });

        session()->flash('success', 'Programación eliminada correctamente.');
    }

    /**
     * Cierra el formulario de programación.
     */
    public function cerrarModalProgramacion(): void
    {
        $this->mostrarModalProgramacion = false;
        $this->resetValidation();
        $this->resetFormProgramacion();
    }

    /**
     * Prepara el formulario de despacho a partir de una programación.
     */
    public function crearDesdeProgramacion($id): void
    {
        Gate::authorize('preparar_despachos');

        $programacion = ProgramacionDespacho::query()
            ->with(['detalles', 'sede'])
            ->find($id);

        if (! $programacion) {
            session()->flash('error', 'La programación solicitada no existe.');

            return;
        }

        if (! $programacion->activa) {
            session()->flash('warning', 'La programación está pausada. Actívala antes de preparar el despacho.');

            return;
        }

        $this->resetValidation();
        $this->resetForm();
        $this->sede_id = $programacion->sede_id;
        $this->fecha_despacho = now()->toDateString();
        $this->observaciones = $programacion->observaciones ?? '';
        $this->items = [];

        $lotesUsados = [];
        $sinLote = [];

        foreach ($programacion->detalles as $detalle) {
            if (! $detalle->producto_id) {
                $sinLote[] = $detalle->producto_nombre;

                continue;
            }

            $lote = Lote::query()
                ->with('producto')
                ->where('producto_id', $detalle->producto_id)
                ->where('stock_actual', '>', 0)
                ->when(! empty($lotesUsados), fn ($query) => $query->whereNotIn('id', $lotesUsados))
                ->orderByRaw('fecha_vencimiento IS NULL')
                ->orderBy('fecha_vencimiento')
                ->orderBy('id')
                ->first();

            if (! $lote) {
                $sinLote[] = $detalle->producto_nombre;

                continue;
            }

            $this->items[] = [
                'lote_id' => $lote->id,
                'cantidad' => (float) $detalle->cantidad,
            ];

            $lotesUsados[] = $lote->id;
        }

        $this->mostrarModal = true;

        if ($sinLote !== []) {
            session()->flash('warning', 'Sin stock para: '.implode(', ', $sinLote).'. Selecciona los lotes manualmente.');
        } else {
            session()->flash('info', 'Borrador cargado desde la programación. Revisa lotes y cantidades antes de registrar.');
        }

        $this->dispatch('enfocar-buscador');
    }

    /**
     * Agrega un lote al despacho desde el buscador.
     */
    public function agregarLote($loteId): void
    {
        Gate::authorize('preparar_despachos');

        $lote = Lote::query()->with('producto')->find($loteId);

        if (! $lote) {
            session()->flash('error', 'El lote seleccionado no existe.');

            return;
        }

        if ((float) $lote->stock_actual <= 0) {
            session()->flash('warning', "El lote {$lote->numero_lote} no tiene stock disponible.");

            return;
        }

        foreach ($this->items as $item) {
            if ((int) ($item['lote_id'] ?? 0) === (int) $lote->id) {
                $this->loteBusqueda = '';
                session()->flash('info', "El lote {$lote->numero_lote} ya está agregado al despacho.");

                return;
            }
        }

        if (count($this->items) >= 20) {
            session()->flash('warning', 'El despacho admite un máximo de 20 lotes diferentes.');

            return;
        }

        $this->items[] = [
            'lote_id' => $lote->id,
            'cantidad' => '',
        ];

        $this->loteBusqueda = '';
        $this->resetValidation();
        $this->dispatch('enfocar-cantidad', index: count($this->items) - 1);
    }

    /**
     * Agrega el primer resultado del buscador (útil para lectores de código).
     */
    public function agregarPrimerLote(): void
    {
        Gate::authorize('preparar_despachos');

        $lote = $this->resultadosLotes()->first();

        if (! $lote) {
            $this->addError('loteBusqueda', 'No se encontraron lotes con stock para esa búsqueda.');

            return;
        }

        $this->agregarLote($lote->id);
    }

    /**
     * Devuelve el foco al buscador para seguir agregando lotes.
     */
    public function enfocarBuscador(): void
    {
        $this->dispatch('enfocar-buscador');
    }

    /**
     * Quita una línea de lote del despacho.
     */
    public function quitarItem(int $index): void
    {
        Gate::authorize('preparar_despachos');

        unset($this->items[$index]);
        $this->items = array_values($this->items);
        $this->resetValidation();

        if (empty($this->items)) {
            $this->dispatch('enfocar-buscador');
        }
    }

    /**
     * Valida, descuenta el stock del lote seleccionado y registra el despacho.
     */
    public function guardar(): void
    {
        Gate::authorize('confirmar_despachos');

        $this->validate();
        $this->validarStockDisponible();

        $fecha = Carbon::parse($this->fecha_despacho)->format('Y-m-d');
        $sede = Sede::query()->with('responsable')->findOrFail($this->sede_id);

        $despachadoPor = $this->jefeAlmacen();

        if (! $despachadoPor) {
            throw ValidationException::withMessages([
                'despachado_por' => 'No hay un empleado activo con el cargo «Jefe de Almacén». Regístralo en el módulo de Empleados.',
            ]);
        }

        $recibidoPor = $this->jefeCentroDeSede($sede);

        if (! $recibidoPor) {
            throw ValidationException::withMessages([
                'recibido_por' => 'La sede seleccionada no tiene un responsable con el cargo «Jefe de Centro».',
            ]);
        }

        $despacho = null;

        DB::transaction(function () use (&$despacho, $fecha, $sede, $despachadoPor, $recibidoPor) {
            $despacho = Despacho::create([
                'numero' => $this->generarNumero($fecha),
                'sede_id' => $sede->id,
                'user_id' => auth()->id(),
                'despachado_por_id' => $despachadoPor->id,
                'despachado_por_nombre' => $this->nombreEmpleado($despachadoPor),
                'recibido_por_id' => $recibidoPor->id,
                'recibido_por_nombre' => $this->nombreEmpleado($recibidoPor),
                'fecha_despacho' => $fecha,
                'observaciones' => filled($this->observaciones) ? $this->observaciones : null,
                'estado' => Despacho::ESTADO_REGISTRADO,
                'consumo_interno' => $sede->tipo === 'principal',
            ]);

            foreach ($this->items as $item) {
                $this->despacharLote(
                    $despacho,
                    (int) $item['lote_id'],
                    (float) $item['cantidad'],
                );
            }

            $despacho->load(['detalles', 'sede']);

            Auditoria::create([
                'user_id' => auth()->id(),
                'accion' => 'despacho_registrado',
                'modelo' => 'despacho',
                'modelo_id' => $despacho->id,
                'descripcion' => "Despacho {$despacho->numero} registrado para {$sede->nombre}",
                'datos_anteriores' => null,
                'datos_nuevos' => $this->datosAuditoria($despacho),
            ]);
        });

        $this->cerrarModal();

        $this->dispatch('descargar-nota-entrega', url: route('despachos.nota-entrega', $despacho));

        session()->flash('success', 'Despacho registrado correctamente.');
    }

    /**
     * Abre el detalle de un despacho.
     */
    public function verDetalle($id): void
    {
        Gate::authorize('ver_despachos');

        if (! Despacho::query()->whereKey($id)->exists()) {
            session()->flash('error', 'El despacho solicitado no existe.');

            return;
        }

        $this->detalleId = $id;
    }

    /**
     * Cierra el detalle del despacho.
     */
    public function cerrarDetalle(): void
    {
        $this->detalleId = null;
    }

    /**
     * Anula un despacho y devuelve las cantidades a sus lotes de origen.
     */
    public function anular($id): void
    {
        Gate::authorize('anular_despachos');

        $despacho = Despacho::query()->with('detalles.lote')->find($id);

        if (! $despacho) {
            session()->flash('error', 'El despacho solicitado no existe.');

            return;
        }

        if ($despacho->estado === Despacho::ESTADO_ANULADO) {
            session()->flash('warning', 'Este despacho ya se encuentra anulado.');

            return;
        }

        DB::transaction(function () use ($despacho) {
            $anteriores = $this->datosAuditoria($despacho);

            foreach ($despacho->detalles as $detalle) {
                if ($detalle->lote) {
                    $detalle->lote->increment('stock_actual', (float) $detalle->cantidad);
                }
            }

            $despacho->update(['estado' => Despacho::ESTADO_ANULADO]);

            ProgramacionDespacho::query()
                ->where('despacho_origen_id', $despacho->id)
                ->update(['activa' => false]);

            Auditoria::create([
                'user_id' => auth()->id(),
                'accion' => 'despacho_anulado',
                'modelo' => 'despacho',
                'modelo_id' => $despacho->id,
                'descripcion' => "Despacho {$despacho->numero} anulado y stock restaurado",
                'datos_anteriores' => $anteriores,
                'datos_nuevos' => $this->datosAuditoria($despacho->fresh(['detalles', 'sede'])),
            ]);
        });

        session()->flash('success', 'Despacho anulado y stock restaurado correctamente.');
    }

    /**
     * Genera el número de despacho con la fecha y un consecutivo.
     * Ejemplo: DES-2026-11-23-001
     */
    public function generarNumero(string $fecha): string
    {
        $base = 'DES-'.$fecha.'-';

        $ultimo = Despacho::query()
            ->where('numero', 'like', $base.'%')
            ->orderByDesc('numero')
            ->value('numero');

        $consecutivo = $ultimo
            ? ((int) substr($ultimo, strlen($base))) + 1
            : 1;

        return $base.str_pad((string) $consecutivo, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Empleado activo con el cargo de Jefe de Almacén.
     */
    private function jefeAlmacen(): ?Empleado
    {
        return Empleado::query()
            ->with('cargo')
            ->where('activo', true)
            ->orderBy('id')
            ->get()
            ->first(fn (Empleado $empleado) => $this->cargoCoincide($empleado->cargo?->nombre, 'jefe de almacen'));
    }

    /**
     * Empleado responsable de la sede que tenga el cargo de Jefe de Centro.
     */
    private function jefeCentroDeSede(?Sede $sede): ?Empleado
    {
        $responsable = $sede?->responsable;

        if (! $responsable || ! $this->cargoCoincide($responsable->cargo?->nombre, 'jefe de centro')) {
            return null;
        }

        return $responsable;
    }

    /**
     * Compara cargos sin importar mayúsculas ni acentos.
     */
    private function cargoCoincide(?string $cargo, string $objetivo): bool
    {
        $normalizar = fn (?string $valor) => Str::lower(Str::ascii(trim((string) $valor)));

        $cargoNormalizado = $normalizar($cargo);
        $objetivoNormalizado = $normalizar($objetivo);

        return $cargoNormalizado !== '' && str_contains($cargoNormalizado, $objetivoNormalizado);
    }

    /**
     * Nombre completo de un empleado.
     */
    private function nombreEmpleado(Empleado $empleado): string
    {
        return trim("{$empleado->nombres} {$empleado->apellidos}");
    }

    /**
     * Lotes con stock que coinciden con la búsqueda y aún no están agregados.
     */
    private function resultadosLotes()
    {
        $termino = trim($this->loteBusqueda);

        $seleccionados = collect($this->items)
            ->pluck('lote_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        return Lote::query()
            ->with('producto')
            ->where('stock_actual', '>', 0)
            ->when($termino !== '', function ($query) use ($termino) {
                $lower = mb_strtolower($termino);
                $like = '%'.$lower.'%';

                $query->where(function ($query) use ($like) {
                    $query->whereRaw('LOWER(numero_lote) LIKE ?', [$like])
                        ->orWhereHas('producto', function ($query) use ($like) {
                            $query->whereRaw('LOWER(nombre) LIKE ?', [$like])
                                ->orWhereRaw('LOWER(sku) LIKE ?', [$like]);
                        });
                })
                    ->orderByRaw('CASE WHEN LOWER(numero_lote) = ? THEN 0 ELSE 1 END', [$lower]);
            })
            ->when(! empty($seleccionados), fn ($query) => $query->whereNotIn('id', $seleccionados))
            ->orderByRaw('fecha_vencimiento IS NULL')
            ->orderBy('fecha_vencimiento')
            ->orderBy('id')
            ->limit(8)
            ->get();
    }

    /**
     * Verifica que cada lote tenga stock suficiente para la cantidad solicitada.
     */
    private function validarStockDisponible(): void
    {
        $solicitado = [];

        foreach ($this->items as $item) {
            $loteId = (int) $item['lote_id'];
            $solicitado[$loteId] = ($solicitado[$loteId] ?? 0) + (float) $item['cantidad'];
        }

        foreach ($solicitado as $loteId => $cantidad) {
            $lote = Lote::query()->with('producto')->find($loteId);
            $disponible = (float) ($lote?->stock_actual ?? 0);

            if (! $lote) {
                throw ValidationException::withMessages([
                    'items' => 'Uno de los lotes seleccionados ya no existe.',
                ]);
            }

            if ($cantidad > $disponible + 0.00001) {
                throw ValidationException::withMessages([
                    'items' => "Stock insuficiente en el lote {$lote->numero_lote} de «"
                        .($lote->producto?->nombre ?? 'producto')
                        .'». Disponible: '.number_format($disponible, 2, ',', '.')
                        .' / Solicitado: '.number_format($cantidad, 2, ',', '.').'.',
                ]);
            }
        }
    }

    /**
     * Descuenta la cantidad indicada del lote seleccionado.
     */
    private function despacharLote(Despacho $despacho, int $loteId, float $cantidad): void
    {
        $lote = Lote::query()
            ->with('producto')
            ->lockForUpdate()
            ->find($loteId);

        if (! $lote) {
            throw ValidationException::withMessages([
                'items' => 'Uno de los lotes seleccionados ya no existe.',
            ]);
        }

        $disponible = (float) $lote->stock_actual;

        if ($cantidad > $disponible + 0.00001) {
            throw ValidationException::withMessages([
                'items' => "Stock insuficiente en el lote {$lote->numero_lote} de «"
                    .($lote->producto?->nombre ?? 'producto')
                    .'». Disponible: '.number_format($disponible, 2, ',', '.').'.',
            ]);
        }

        $lote->update(['stock_actual' => round($disponible - $cantidad, 2)]);

        DespachoDetalle::create([
            'despacho_id' => $despacho->id,
            'producto_id' => $lote->producto?->id,
            'lote_id' => $lote->id,
            'producto_nombre' => $lote->producto?->nombre ?? 'Producto eliminado',
            'numero_lote' => $lote->numero_lote,
            'cantidad' => $cantidad,
        ]);
    }

    /**
     * Cambia el estado activo de una programación y lo audita.
     */
    private function cambiarEstadoProgramacion(int $id, bool $activa, string $accion, string $mensaje): void
    {
        Gate::authorize('programar_despachos');

        $programacion = ProgramacionDespacho::query()->find($id);

        if (! $programacion) {
            session()->flash('error', 'La programación solicitada no existe.');

            return;
        }

        $anteriores = ['activa' => (bool) $programacion->activa];

        DB::transaction(function () use ($programacion, $activa, $accion, $anteriores) {
            $programacion->update(['activa' => $activa]);

            Auditoria::create([
                'user_id' => auth()->id(),
                'accion' => $accion,
                'modelo' => 'programacion_despacho',
                'modelo_id' => $programacion->id,
                'descripcion' => $activa
                    ? 'Programación de envío activada'
                    : 'Programación de envío pausada',
                'datos_anteriores' => $anteriores,
                'datos_nuevos' => ['activa' => $activa],
            ]);
        });

        session()->flash('success', $mensaje);
    }

    /**
     * Datos de la programación que se guardan en la auditoría.
     */
    private function datosAuditoriaProgramacion(ProgramacionDespacho $programacion): array
    {
        $programacion->loadMissing(['detalles', 'sede']);

        return [
            'despacho_origen_id' => $programacion->despacho_origen_id,
            'sede' => $programacion->sede?->nombre,
            'cada_dias' => $programacion->cada_dias,
            'fecha_inicio' => $programacion->fecha_inicio?->format('Y-m-d'),
            'proxima_fecha' => $programacion->proxima_fecha?->toDateTimeString(),
            'activa' => (bool) $programacion->activa,
            'observaciones' => $programacion->observaciones,
            'productos' => $programacion->detalles->map(fn ($detalle) => [
                'producto' => $detalle->producto_nombre,
                'cantidad' => (float) $detalle->cantidad,
            ])->all(),
        ];
    }

    /**
     * Datos del despacho que se guardan en la auditoría.
     */
    private function datosAuditoria(Despacho $despacho): array
    {
        $despacho->loadMissing(['detalles', 'sede']);

        return [
            'numero' => $despacho->numero,
            'sede' => $despacho->sede?->nombre,
            'fecha_despacho' => $despacho->fecha_despacho?->format('Y-m-d'),
            'estado' => $despacho->estado,
            'consumo_interno' => (bool) $despacho->consumo_interno,
            'despachado_por' => $despacho->despachado_por_nombre,
            'recibido_por' => $despacho->recibido_por_nombre,
            'observaciones' => $despacho->observaciones,
            'total_unidades' => (float) $despacho->detalles->sum('cantidad'),
            'detalles' => $despacho->detalles->map(fn (DespachoDetalle $detalle) => [
                'producto' => $detalle->producto_nombre,
                'numero_lote' => $detalle->numero_lote,
                'cantidad' => (float) $detalle->cantidad,
            ])->all(),
        ];
    }

    /**
     * Cierra el formulario y limpia sus datos.
     */
    public function cerrarModal(): void
    {
        $this->mostrarModal = false;
        $this->resetValidation();
        $this->resetForm();
    }

    /**
     * Limpia las propiedades del formulario.
     */
    private function resetForm(): void
    {
        $this->reset(['sede_id', 'fecha_despacho', 'observaciones', 'loteBusqueda', 'items']);

        $this->fecha_despacho = now()->format('Y-m-d');
        $this->items = [];
    }

    /**
     * Limpia las propiedades del formulario de programación.
     */
    private function resetFormProgramacion(): void
    {
        $this->reset([
            'programacionId',
            'programacionDespachoId',
            'programacionSedeId',
            'programacionCadaDias',
            'programacionFechaInicio',
            'programacionObservaciones',
            'programacionDetalles',
        ]);

        $this->programacionCadaDias = 7;
        $this->programacionFechaInicio = now()->addDays(7)->format('Y-m-d');
        $this->programacionDetalles = [];
    }

    /**
     * Lista los despachos con búsqueda y paginación.
     */
    protected function listar()
    {
        return Despacho::query()
            ->with(['sede', 'user', 'despachadoPor', 'recibidoPor'])
            ->withCount('detalles')
            ->withCount(['programaciones as programaciones_activas_count' => fn ($query) => $query->where('activa', true)])
            ->withSum('detalles as total_unidades', 'cantidad')
            ->when($this->search !== '', function ($query) {
                $termino = '%'.mb_strtolower(trim($this->search)).'%';

                $query->where(function ($query) use ($termino) {
                    $query->whereRaw('LOWER(numero) LIKE ?', [$termino])
                        ->orWhereHas('sede', function ($query) use ($termino) {
                            $query->whereRaw('LOWER(nombre) LIKE ?', [$termino]);
                        })
                        ->orWhereHas('detalles', function ($query) use ($termino) {
                            $query->whereRaw('LOWER(producto_nombre) LIKE ?', [$termino]);
                        });
                });
            })
            ->orderByDesc('fecha_despacho')
            ->orderByDesc('id')
            ->paginate(10);
    }

    /**
     * Renderiza el listado, los catálogos y el detalle seleccionado.
     */
    public function render()
    {
        $detalle = $this->detalleId
            ? Despacho::query()
                ->with([
                    'sede',
                    'user',
                    'despachadoPor',
                    'recibidoPor',
                    'detalles.producto',
                    'detalles.lote',
                ])
                ->find($this->detalleId)
            : null;

        $sedeSeleccionada = filled($this->sede_id)
            ? Sede::query()->with('responsable.cargo')->find($this->sede_id)
            : null;

        $loteIds = collect($this->items)
            ->pluck('lote_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        return view('livewire.gestion-despachos', [
            'despachos' => $this->listar(),
            'sedes' => Sede::query()->where('activo', true)->orderBy('nombre')->get(),
            'sedeSeleccionada' => $sedeSeleccionada,
            'jefeAlmacen' => $this->jefeAlmacen(),
            'jefeCentro' => $this->jefeCentroDeSede($sedeSeleccionada),
            'lotesDisponibles' => $this->resultadosLotes(),
            'lotesSeleccionados' => Lote::query()
                ->with('producto')
                ->whereIn('id', $loteIds)
                ->get()
                ->keyBy('id'),
            'detalle' => $detalle,
            'programaciones' => ProgramacionDespacho::query()
                ->with(['sede', 'detalles'])
                ->orderByDesc('activa')
                ->orderBy('proxima_fecha')
                ->limit(10)
                ->get(),
        ]);
    }
}
