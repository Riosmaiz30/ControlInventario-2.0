<?php

namespace App\Livewire;

use App\Models\Auditoria;
use App\Models\Configuracion;
use App\Models\Lote;
use App\Models\Producto;
use App\Models\Proveedor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class GestionLotes extends Component
{
    use WithPagination;

    /** Texto de búsqueda por número de lote o nombre del producto. */
    public string $search = '';

    /** Propiedades del formulario de entrada. */
    public $lote_id = null;

    public $producto_id = '';

    public $proveedor_id = '';

    public $numero_lote = '';

    public $stock_inicial = '';

    public $fecha_registro = '';

    public $fecha_vencimiento = '';

    /** Datos originales del lote en edición. */
    public $numero_lote_original = '';

    public $proveedor_id_original = '';

    public $fecha_registro_original = '';

    /** Indica si el formulario está editando un lote existente. */
    public bool $isEditing = false;

    /** Controla la visibilidad del formulario. */
    public bool $mostrarModal = false;

    /**
     * Reglas de validación estrictas para el registro de entradas.
     */
    protected function rules(): array
    {
        return [
            'producto_id' => ['required', 'integer', 'exists:productos,id'],
            'proveedor_id' => ['required', 'integer', 'exists:proveedores,id'],
            'numero_lote' => ['required', 'string', 'max:255'],
            'stock_inicial' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'fecha_registro' => ['required', 'date'],
            'fecha_vencimiento' => ['required', 'date', 'after_or_equal:fecha_registro'],
        ];
    }

    /**
     * Mensajes de validación en español.
     */
    protected function messages(): array
    {
        return [
            'producto_id.required' => 'Debe seleccionar un producto.',
            'producto_id.integer' => 'El producto seleccionado no es válido.',
            'producto_id.exists' => 'El producto seleccionado no existe.',
            'proveedor_id.required' => 'Debe seleccionar un proveedor.',
            'proveedor_id.integer' => 'El proveedor seleccionado no es válido.',
            'proveedor_id.exists' => 'El proveedor seleccionado no existe.',
            'numero_lote.required' => 'El número de lote es obligatorio.',
            'numero_lote.max' => 'El número de lote no puede superar los 255 caracteres.',
            'stock_inicial.required' => 'El stock inicial es obligatorio.',
            'stock_inicial.numeric' => 'El stock inicial debe ser un número.',
            'stock_inicial.gt' => 'El stock inicial debe ser mayor a 0.',
            'stock_inicial.max' => 'El stock inicial es demasiado grande.',
            'fecha_registro.required' => 'La fecha de registro es obligatoria.',
            'fecha_registro.date' => 'La fecha de registro no es válida.',
            'fecha_vencimiento.required' => 'La fecha de vencimiento es obligatoria para el control FIFO.',
            'fecha_vencimiento.date' => 'La fecha de vencimiento no es válida.',
            'fecha_vencimiento.after_or_equal' => 'La fecha de vencimiento debe ser igual o posterior a la fecha de registro.',
        ];
    }

    /**
     * Nombres legibles de los campos para los mensajes de validación.
     */
    protected function validationAttributes(): array
    {
        return [
            'producto_id' => 'producto',
            'proveedor_id' => 'proveedor',
            'numero_lote' => 'número de lote',
            'stock_inicial' => 'stock inicial',
            'fecha_registro' => 'fecha de registro',
            'fecha_vencimiento' => 'fecha de vencimiento',
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
     * Regenera el número de lote al seleccionar un proveedor.
     */
    public function updatedProveedorId(): void
    {
        $this->numero_lote = $this->generarNumeroLote($this->isEditing ? $this->lote_id : null);
    }

    /**
     * Regenera el número de lote cuando cambia la fecha de registro.
     */
    public function updatedFechaRegistro(): void
    {
        $this->numero_lote = $this->generarNumeroLote($this->isEditing ? $this->lote_id : null);
    }

    /**
     * Abre el formulario para registrar una nueva entrada.
     */
    public function crear(): void
    {
        Gate::authorize('registrar_lotes');

        $this->resetValidation();
        $this->resetForm();
        $this->isEditing = false;
        $this->mostrarModal = true;
    }

    /**
     * Verifica la clave de administrador y abre la edición del lote.
     */
    public function solicitarEdicion(int $id, string $clave): void
    {
        Gate::authorize('modificar_lotes');

        if (! Configuracion::tieneClaveAdministrador()) {
            session()->flash('warning', 'No hay una clave de administrador configurada. Configúrala en Auditoría del Sistema.');

            return;
        }

        if (! Configuracion::verificarClaveAdministrador($clave)) {
            session()->flash('error', 'La clave de administrador es incorrecta.');

            return;
        }

        $lote = Lote::find($id);

        if (! $lote) {
            session()->flash('error', 'El lote ya no existe.');

            return;
        }

        $this->resetValidation();
        $this->resetForm();

        $this->isEditing = true;
        $this->lote_id = $lote->id;
        $this->producto_id = $lote->producto_id;
        $this->proveedor_id = $lote->proveedor_id;
        $this->numero_lote = $lote->numero_lote;
        $this->stock_inicial = $lote->stock_inicial;
        $this->fecha_registro = $lote->fecha_registro?->format('Y-m-d') ?? now()->format('Y-m-d');
        $this->fecha_vencimiento = $lote->fecha_vencimiento?->format('Y-m-d') ?? '';

        $this->numero_lote_original = $lote->numero_lote;
        $this->proveedor_id_original = $lote->proveedor_id;
        $this->fecha_registro_original = $this->fecha_registro;

        $this->mostrarModal = true;
    }

    /**
     * Valida y registra la entrada. El stock actual inicia igual al stock inicial.
     */
    public function guardar(): void
    {
        Gate::authorize('registrar_lotes');

        // Se recalcula para tomar el siguiente consecutivo disponible.
        $this->numero_lote = $this->generarNumeroLote();

        $datos = $this->validate();

        // Toda entrada nueva comienza con el total de unidades recibidas.
        $datos['stock_actual'] = $datos['stock_inicial'];

        Lote::create($datos);

        $this->cerrarModal();

        session()->flash('success', 'Entrada de mercancía registrada correctamente.');
    }

    /**
     * Valida y actualiza el lote en edición.
     */
    public function actualizar(): void
    {
        Gate::authorize('modificar_lotes');

        $lote = Lote::findOrFail($this->lote_id);

        $datos = $this->validate();
        $datos['numero_lote'] = $this->generarNumeroLote($lote->id);

        // Se conservan los movimientos ya realizados ajustando solo la diferencia.
        $datos['stock_actual'] = max(
            0,
            (float) $lote->stock_actual + ((float) $datos['stock_inicial'] - (float) $lote->stock_inicial)
        );

        $anteriores = $this->datosAuditoria($lote);

        $lote->update($datos);

        Auditoria::create([
            'user_id' => auth()->id(),
            'accion' => 'lote_actualizado',
            'modelo' => 'lote',
            'modelo_id' => $lote->id,
            'descripcion' => "Lote {$lote->numero_lote} actualizado",
            'datos_anteriores' => $anteriores,
            'datos_nuevos' => $this->datosAuditoria($lote->fresh()),
        ]);

        $this->cerrarModal();

        session()->flash('success', 'Entrada de mercancía actualizada correctamente.');
    }

    /**
     * Cierra el formulario.
     */
    public function cerrarModal(): void
    {
        $this->mostrarModal = false;
        $this->resetValidation();
        $this->resetForm();
    }

    /**
     * Genera el número de lote con el proveedor, la fecha y un consecutivo.
     * Ejemplo: Dis-la-ori-2026-11-17-001
     */
    public function generarNumeroLote(?int $excluirId = null): string
    {
        if (
            $this->isEditing
            && filled($this->numero_lote_original)
            && (int) $this->proveedor_id === (int) $this->proveedor_id_original
            && $this->fecha_registro === $this->fecha_registro_original
        ) {
            return $this->numero_lote_original;
        }

        $proveedor = Proveedor::find($this->proveedor_id);

        if (! $proveedor || blank($this->fecha_registro)) {
            return '';
        }

        try {
            $fecha = Carbon::parse($this->fecha_registro)->format('Y-m-d');
        } catch (\Exception) {
            return '';
        }

        $base = $this->prefijoProveedor($proveedor->nombre).'-'.$fecha.'-';

        $consulta = Lote::query()
            ->where('numero_lote', 'like', $base.'%');

        if ($excluirId) {
            $consulta->where('id', '!=', $excluirId);
        }

        $ultimo = $consulta->orderByDesc('numero_lote')->value('numero_lote');

        $consecutivo = $ultimo
            ? ((int) substr($ultimo, strlen($base))) + 1
            : 1;

        return $base.str_pad((string) $consecutivo, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Construye el prefijo con las tres primeras letras de cada palabra.
     */
    private function prefijoProveedor(string $nombre): string
    {
        $palabras = preg_split('/\s+/', trim(Str::ascii($nombre))) ?: [];
        $partes = [];

        foreach ($palabras as $palabra) {
            $letras = preg_replace('/[^A-Za-z]/', '', $palabra);

            if ($letras !== '') {
                $partes[] = substr($letras, 0, 3);
            }
        }

        $prefijo = implode('-', $partes);

        return $prefijo === '' ? 'LOTE' : ucfirst(strtolower($prefijo));
    }

    /**
     * Datos del lote que se guardan en la auditoría.
     */
    private function datosAuditoria(Lote $lote): array
    {
        return [
            'producto_id' => $lote->producto_id,
            'proveedor_id' => $lote->proveedor_id,
            'numero_lote' => $lote->numero_lote,
            'stock_inicial' => (float) $lote->stock_inicial,
            'stock_actual' => (float) $lote->stock_actual,
            'fecha_registro' => $lote->fecha_registro?->format('Y-m-d'),
            'fecha_vencimiento' => $lote->fecha_vencimiento?->format('Y-m-d'),
        ];
    }

    /**
     * Limpia las propiedades del formulario.
     */
    private function resetForm(): void
    {
        $this->reset([
            'lote_id',
            'producto_id',
            'proveedor_id',
            'numero_lote',
            'stock_inicial',
            'fecha_registro',
            'fecha_vencimiento',
            'numero_lote_original',
            'proveedor_id_original',
            'fecha_registro_original',
            'isEditing',
        ]);

        $this->fecha_registro = now()->format('Y-m-d');
    }

    /**
     * Lista los lotes con búsqueda y paginación.
     */
    protected function listar()
    {
        return Lote::query()
            ->with(['producto', 'proveedor'])
            ->when($this->search !== '', function ($query) {
                $termino = '%'.mb_strtolower(trim($this->search)).'%';

                $query->where(function ($query) use ($termino) {
                    $query->whereRaw('LOWER(numero_lote) LIKE ?', [$termino])
                        ->orWhereHas('producto', function ($query) use ($termino) {
                            $query->whereRaw('LOWER(nombre) LIKE ?', [$termino]);
                        });
                });
            })
            ->orderByRaw('fecha_vencimiento IS NULL')
            ->orderBy('fecha_vencimiento')
            ->orderByDesc('id')
            ->paginate(10);
    }

    /**
     * Renderiza la tabla y envía productos y proveedores a los desplegables.
     */
    public function render()
    {
        return view('livewire.gestion-lotes', [
            'lotes' => $this->listar(),
            'productos' => Producto::all(),
            'proveedores' => Proveedor::all(),
            'claveConfigurada' => Configuracion::tieneClaveAdministrador(),
        ]);
    }
}
