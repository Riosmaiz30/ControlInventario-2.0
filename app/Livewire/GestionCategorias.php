<?php

namespace App\Livewire;

use App\Models\Categoria;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class GestionCategorias extends Component
{
    use WithPagination;

    public string $search = '';

    public $categoria_id = null;

    public $nombre = '';

    public $descripcion = '';

    public bool $isEditing = false;

    public bool $mostrarModal = false;

    protected function rules(): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categorias', 'nombre')->ignore($this->categoria_id),
            ],
            'descripcion' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la categoría es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 255 caracteres.',
            'nombre.unique' => 'Ya existe una categoría con ese nombre.',
            'descripcion.max' => 'La descripción no puede superar los 1000 caracteres.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'nombre' => 'nombre',
            'descripcion' => 'descripción',
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function crear(): void
    {
        Gate::authorize('gestionar_catalogos');

        $this->resetValidation();
        $this->resetForm();
        $this->isEditing = false;
        $this->mostrarModal = true;
    }

    public function guardar(): void
    {
        Gate::authorize('gestionar_catalogos');

        $datos = $this->validate();

        Categoria::create($datos);

        $this->cerrarModal();
        $this->resetForm();

        session()->flash('success', 'Categoría registrada correctamente.');
    }

    public function editar($id): void
    {
        Gate::authorize('gestionar_catalogos');

        $categoria = Categoria::findOrFail($id);

        $this->resetValidation();
        $this->categoria_id = $categoria->id;
        $this->nombre = $categoria->nombre;
        $this->descripcion = $categoria->descripcion ?? '';
        $this->isEditing = true;
        $this->mostrarModal = true;
    }

    public function actualizar(): void
    {
        Gate::authorize('gestionar_catalogos');

        if (! $this->categoria_id) {
            return;
        }

        $categoria = Categoria::findOrFail($this->categoria_id);
        $datos = $this->validate();

        $categoria->update($datos);

        $this->cerrarModal();
        $this->resetForm();

        session()->flash('success', 'Categoría actualizada correctamente.');
    }

    public function eliminar($id): void
    {
        Gate::authorize('gestionar_catalogos');

        $categoria = Categoria::findOrFail($id);

        if ($categoria->productos()->exists()) {
            session()->flash('error', 'No se puede eliminar la categoría porque tiene productos asociados.');

            return;
        }

        $categoria->delete();

        if ((int) $this->categoria_id === (int) $id) {
            $this->cerrarModal();
            $this->resetForm();
        }

        session()->flash('success', 'Categoría eliminada correctamente.');
    }

    public function cerrarModal(): void
    {
        $this->mostrarModal = false;
        $this->resetValidation();
    }

    private function resetForm(): void
    {
        $this->reset([
            'categoria_id',
            'nombre',
            'descripcion',
            'isEditing',
        ]);
    }

    protected function listar()
    {
        return Categoria::query()
            ->withCount('productos')
            ->when($this->search !== '', function ($query) {
                $texto = trim($this->search);
                $termino = '%'.mb_strtolower($texto).'%';

                $query->where(function ($query) use ($texto, $termino) {
                    $query->whereRaw('LOWER(nombre) LIKE ?', [$termino])
                        ->orWhereRaw('LOWER(COALESCE(descripcion, \'\')) LIKE ?', [$termino]);

                    // Permite buscar por el código mostrado, por ejemplo CAT-0001.
                    if (preg_match('/^cat-?0*(\d+)$/i', $texto, $coincidencia)) {
                        $query->orWhere('id', (int) $coincidencia[1]);
                    }
                });
            })
            ->orderBy('nombre')
            ->paginate(10);
    }

    public function render()
    {
        return view('livewire.gestion-categorias', [
            'categorias' => $this->listar(),
        ]);
    }
}
