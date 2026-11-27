<?php

namespace App\Livewire;

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class GestionProductos extends Component
{
    use WithPagination;

    /**
     * Unidades de medida disponibles en el formulario.
     */
    public const UNIDADES = [
        'unidad' => 'Unidad',
        'kg' => 'Kilogramo (kg)',
        'g' => 'Gramo (g)',
        'litro' => 'Litro (L)',
        'ml' => 'Mililitro (ml)',
        'paquete' => 'Paquete',
        'caja' => 'Caja',
        'rollo' => 'Rollo',
        'docena' => 'Docena',
    ];

    /**
     * Tipos de empaque disponibles para el producto.
     */
    public const TIPOS_EMPAQUE = [
        'bulto' => 'Bulto',
        'paca' => 'Paca',
    ];

    /** Texto de búsqueda por nombre o código de producto. */
    public string $search = '';

    /** Filtro por marca: identificador, "sin-marca" o vacío para todas. */
    public string $marcaFiltro = '';

    /** Propiedades del formulario. */
    public $producto_id = null;

    public $nombre = '';

    public $sku = '';

    public $categoria_id = '';

    public $marca_id = '';

    public $unidad_medida = '';

    public $tipo_empaque = '';

    public $cantidad_empaque = '';

    public $stock_minimo_alerta = 0;

    /** Estado del formulario. */
    public bool $isEditing = false;

    public bool $mostrarModal = false;

    /** Alta rápida de marcas desde el formulario de productos. */
    public bool $mostrarModalMarca = false;

    public string $nuevoMarcaNombre = '';

    public string $nuevoMarcaDescripcion = '';

    /**
     * Reglas de validación compartidas por crear y actualizar.
     */
    protected function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'categoria_id' => ['required', 'integer', 'exists:categorias,id'],
            'marca_id' => ['nullable', 'integer', 'exists:marcas,id'],
            'unidad_medida' => ['required', 'string', Rule::in(array_keys(self::UNIDADES))],
            'tipo_empaque' => [
                'nullable',
                'string',
                Rule::in(array_keys(self::TIPOS_EMPAQUE)),
            ],
            'cantidad_empaque' => [
                Rule::requiredIf(filled($this->tipo_empaque)),
                'nullable',
                'integer',
                'min:1',
                'max:999999',
            ],
            'stock_minimo_alerta' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ];
    }

    /**
     * Mensajes de validación en español.
     */
    protected function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del producto es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 255 caracteres.',
            'categoria_id.required' => 'Debe seleccionar una categoría.',
            'categoria_id.integer' => 'La categoría seleccionada no es válida.',
            'categoria_id.exists' => 'La categoría seleccionada no existe.',
            'marca_id.integer' => 'La marca seleccionada no es válida.',
            'marca_id.exists' => 'La marca seleccionada no existe.',
            'unidad_medida.required' => 'La unidad de medida es obligatoria.',
            'unidad_medida.in' => 'La unidad de medida seleccionada no es válida.',
            'tipo_empaque.in' => 'El tipo de empaque seleccionado no es válido.',
            'cantidad_empaque.required' => 'Debe indicar la cantidad que trae el bulto o paca.',
            'cantidad_empaque.integer' => 'La cantidad del empaque debe ser un número entero.',
            'cantidad_empaque.min' => 'La cantidad del empaque debe ser al menos 1.',
            'cantidad_empaque.max' => 'La cantidad del empaque es demasiado grande.',
            'stock_minimo_alerta.required' => 'El stock mínimo de alerta es obligatorio.',
            'stock_minimo_alerta.numeric' => 'El stock mínimo de alerta debe ser un número.',
            'stock_minimo_alerta.min' => 'El stock mínimo de alerta no puede ser negativo.',
            'stock_minimo_alerta.max' => 'El stock mínimo de alerta es demasiado grande.',
            'nuevoMarcaNombre.required' => 'El nombre de la marca es obligatorio.',
            'nuevoMarcaNombre.max' => 'El nombre de la marca no puede superar los 255 caracteres.',
            'nuevoMarcaNombre.unique' => 'Ya existe una marca con ese nombre.',
            'nuevoMarcaDescripcion.max' => 'La descripción de la marca no puede superar los 1000 caracteres.',
        ];
    }

    /**
     * Nombres legibles de los campos para los mensajes de validación.
     */
    protected function validationAttributes(): array
    {
        return [
            'nombre' => 'nombre',
            'categoria_id' => 'categoría',
            'marca_id' => 'marca',
            'unidad_medida' => 'unidad de medida',
            'tipo_empaque' => 'tipo de empaque',
            'cantidad_empaque' => 'cantidad del empaque',
            'stock_minimo_alerta' => 'stock mínimo de alerta',
            'nuevoMarcaNombre' => 'nombre de la marca',
            'nuevoMarcaDescripcion' => 'descripción de la marca',
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
     * Reinicia la paginación cuando cambia el filtro por marca.
     */
    public function updatedMarcaFiltro(): void
    {
        $this->resetPage();
    }

    /**
     * Muestra una vista previa del código mientras se escribe el nombre.
     */
    public function updatedNombre(): void
    {
        if (! $this->isEditing) {
            $this->sku = filled($this->nombre) ? Producto::generarCodigo($this->nombre) : '';
        }
    }

    /**
     * Limpia la cantidad cuando el producto deja de tener empaque.
     */
    public function updatedTipoEmpaque(): void
    {
        if (! $this->tipo_empaque) {
            $this->cantidad_empaque = '';
        }
    }

    /**
     * Abre el formulario en modo creación.
     */
    public function crear(): void
    {
        Gate::authorize('gestionar_productos');

        $this->resetValidation();
        $this->resetForm();
        $this->isEditing = false;
        $this->mostrarModal = true;
    }

    /**
     * Valida y guarda un producto nuevo.
     */
    public function guardar(): void
    {
        Gate::authorize('gestionar_productos');

        $datos = $this->normalizarDatos($this->validate());

        Producto::create($datos);

        $this->cerrarModal();
        $this->resetForm();

        session()->flash('success', 'Producto registrado correctamente.');
    }

    /**
     * Carga un producto en el formulario para editarlo.
     */
    public function editar($id): void
    {
        Gate::authorize('gestionar_productos');

        $producto = Producto::findOrFail($id);

        $this->resetValidation();
        $this->producto_id = $producto->id;
        $this->nombre = $producto->nombre;
        $this->sku = $producto->sku;
        $this->categoria_id = $producto->categoria_id;
        $this->marca_id = $producto->marca_id ?? '';
        $this->unidad_medida = $producto->unidad_medida;
        $this->tipo_empaque = $producto->tipo_empaque ?? '';
        $this->cantidad_empaque = $producto->cantidad_empaque ?? '';
        $this->stock_minimo_alerta = $producto->stock_minimo_alerta;
        $this->isEditing = true;
        $this->mostrarModal = true;
    }

    /**
     * Valida y actualiza el producto en edición.
     */
    public function actualizar(): void
    {
        Gate::authorize('gestionar_productos');

        if (! $this->producto_id) {
            return;
        }

        $producto = Producto::findOrFail($this->producto_id);
        $datos = $this->normalizarDatos($this->validate());

        $producto->update($datos);

        $this->cerrarModal();
        $this->resetForm();

        session()->flash('success', 'Producto actualizado correctamente.');
    }

    /**
     * Elimina un producto del catálogo.
     */
    public function eliminar($id): void
    {
        Gate::authorize('gestionar_productos');

        $producto = Producto::findOrFail($id);
        $producto->delete();

        if ((int) $this->producto_id === (int) $id) {
            $this->cerrarModal();
            $this->resetForm();
        }

        session()->flash('success', 'Producto eliminado correctamente.');
    }

    /**
     * Cierra el formulario.
     */
    public function cerrarModal(): void
    {
        $this->mostrarModal = false;
        $this->mostrarModalMarca = false;
        $this->resetValidation();
        $this->reset(['nuevoMarcaNombre', 'nuevoMarcaDescripcion']);
    }

    /**
     * Abre el alta rápida de marcas sin cerrar el formulario del producto.
     */
    public function crearMarca(): void
    {
        Gate::authorize('gestionar_productos');

        $this->resetValidation(['nuevoMarcaNombre', 'nuevoMarcaDescripcion']);
        $this->reset(['nuevoMarcaNombre', 'nuevoMarcaDescripcion']);
        $this->mostrarModalMarca = true;
    }

    /**
     * Guarda una marca creada desde el formulario de productos y la selecciona.
     */
    public function guardarMarca(): void
    {
        Gate::authorize('gestionar_productos');

        $datos = $this->validate([
            'nuevoMarcaNombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('marcas', 'nombre'),
            ],
            'nuevoMarcaDescripcion' => ['nullable', 'string', 'max:1000'],
        ]);

        $marca = Marca::create([
            'nombre' => $datos['nuevoMarcaNombre'],
            'descripcion' => $datos['nuevoMarcaDescripcion'] ?? null,
            'activo' => true,
        ]);

        $this->marca_id = $marca->id;
        $this->cerrarModalMarca();

        session()->flash('success', 'Marca registrada correctamente.');
    }

    /**
     * Cierra el alta rápida de marcas.
     */
    public function cerrarModalMarca(): void
    {
        $this->mostrarModalMarca = false;
        $this->resetValidation(['nuevoMarcaNombre', 'nuevoMarcaDescripcion']);
        $this->reset(['nuevoMarcaNombre', 'nuevoMarcaDescripcion']);
    }

    /**
     * Cierra primero el alta rápida de marcas y luego el formulario del producto.
     */
    public function cerrarModalConEscape(): void
    {
        if ($this->mostrarModalMarca) {
            $this->cerrarModalMarca();

            return;
        }

        $this->cerrarModal();
    }

    /**
     * Normaliza los campos opcionales antes de guardar.
     */
    private function normalizarDatos(array $datos): array
    {
        if (blank($datos['tipo_empaque'] ?? null)) {
            $datos['tipo_empaque'] = null;
            $datos['cantidad_empaque'] = null;
        }

        if (blank($datos['marca_id'] ?? null)) {
            $datos['marca_id'] = null;
        }

        return $datos;
    }

    /**
     * Limpia las propiedades del formulario.
     */
    private function resetForm(): void
    {
        $this->reset([
            'producto_id',
            'nombre',
            'sku',
            'categoria_id',
            'marca_id',
            'unidad_medida',
            'tipo_empaque',
            'cantidad_empaque',
            'stock_minimo_alerta',
            'isEditing',
            'mostrarModalMarca',
            'nuevoMarcaNombre',
            'nuevoMarcaDescripcion',
        ]);

        $this->stock_minimo_alerta = 0;
        $this->marca_id = '';
        $this->tipo_empaque = '';
        $this->cantidad_empaque = '';
    }

    /**
     * Renderiza la tabla paginada y envía las categorías y marcas al formulario.
     */
    public function render()
    {
        $productos = Producto::query()
            ->with(['categoria', 'marca'])
            ->withSum('lotes as stock_disponible', 'stock_actual')
            ->when($this->marcaFiltro !== '', function ($query) {
                if ($this->marcaFiltro === 'sin-marca') {
                    $query->whereNull('marca_id');
                } else {
                    $query->where('marca_id', (int) $this->marcaFiltro);
                }
            })
            ->when($this->search !== '', function ($query) {
                $termino = '%'.mb_strtolower(trim($this->search)).'%';

                $query->where(function ($query) use ($termino) {
                    $query->whereRaw('LOWER(nombre) LIKE ?', [$termino])
                        ->orWhereRaw('LOWER(sku) LIKE ?', [$termino])
                        ->orWhereHas('marca', fn ($query) => $query->whereRaw('LOWER(nombre) LIKE ?', [$termino]));
                });
            })
            ->orderByDesc('id')
            ->paginate(10);

        return view('livewire.gestion-productos', [
            'productos' => $productos,
            'categorias' => Categoria::orderBy('nombre')->get(),
            'marcas' => Marca::orderBy('nombre')->get(),
            'unidades' => self::UNIDADES,
            'tiposEmpaque' => self::TIPOS_EMPAQUE,
        ]);
    }
}
