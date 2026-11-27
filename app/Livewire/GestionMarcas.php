<?php

namespace App\Livewire;

use App\Models\Marca;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class GestionMarcas extends Component
{
    use WithPagination;

    public string $search = '';

    public $marca_id = null;

    public string $nombre = '';

    public string $descripcion = '';

    public bool $activo = true;

    public bool $isEditing = false;

    public bool $mostrarModal = false;

    protected function rules(): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('marcas', 'nombre')->ignore($this->marca_id),
            ],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'activo' => ['boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la marca es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 255 caracteres.',
            'nombre.unique' => 'Ya existe una marca con ese nombre.',
            'descripcion.max' => 'La descripción no puede superar los 1000 caracteres.',
            'activo.boolean' => 'El estado activo no es válido.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'nombre' => 'nombre',
            'descripcion' => 'descripción',
            'activo' => 'estado',
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

        Marca::create($datos);

        $this->cerrarModal();
        $this->resetForm();

        session()->flash('success', 'Marca registrada correctamente.');
    }

    public function editar($id): void
    {
        Gate::authorize('gestionar_catalogos');

        $marca = Marca::findOrFail($id);

        $this->resetValidation();
        $this->marca_id = $marca->id;
        $this->nombre = $marca->nombre;
        $this->descripcion = $marca->descripcion ?? '';
        $this->activo = (bool) $marca->activo;
        $this->isEditing = true;
        $this->mostrarModal = true;
    }

    public function actualizar(): void
    {
        Gate::authorize('gestionar_catalogos');

        if (! $this->marca_id) {
            return;
        }

        $marca = Marca::findOrFail($this->marca_id);
        $datos = $this->validate();

        $marca->update($datos);

        $this->cerrarModal();
        $this->resetForm();

        session()->flash('success', 'Marca actualizada correctamente.');
    }

    public function eliminar($id): void
    {
        Gate::authorize('gestionar_catalogos');

        $marca = Marca::findOrFail($id);

        if ($marca->productos()->exists()) {
            session()->flash('error', 'No se puede eliminar la marca porque tiene productos asociados.');

            return;
        }

        $marca->delete();

        if ((int) $this->marca_id === (int) $id) {
            $this->cerrarModal();
            $this->resetForm();
        }

        session()->flash('success', 'Marca eliminada correctamente.');
    }

    public function cerrarModal(): void
    {
        $this->mostrarModal = false;
        $this->resetValidation();
    }

    private function resetForm(): void
    {
        $this->reset([
            'marca_id',
            'nombre',
            'descripcion',
            'activo',
            'isEditing',
        ]);

        $this->activo = true;
    }

    protected function listar()
    {
        return Marca::query()
            ->withCount('productos')
            ->when($this->search !== '', function ($query) {
                $texto = trim($this->search);
                $termino = '%'.mb_strtolower($texto).'%';

                $query->where(function ($query) use ($texto, $termino) {
                    $query->whereRaw('LOWER(nombre) LIKE ?', [$termino])
                        ->orWhereRaw('LOWER(COALESCE(descripcion, \'\')) LIKE ?', [$termino]);

                    // Permite buscar por el código mostrado, por ejemplo MAR-0001.
                    if (preg_match('/^mar-?0*(\d+)$/i', $texto, $coincidencia)) {
                        $query->orWhere('id', (int) $coincidencia[1]);
                    }
                });
            })
            ->orderBy('nombre')
            ->paginate(10);
    }

    public function render()
    {
        return view('livewire.gestion-marcas', [
            'marcas' => $this->listar(),
        ]);
    }
}
