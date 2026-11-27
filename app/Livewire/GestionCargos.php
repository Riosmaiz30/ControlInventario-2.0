<?php

namespace App\Livewire;

use App\Models\Cargo;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class GestionCargos extends Component
{
    use WithPagination;

    public string $search = '';

    public $cargo_id = null;

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
                Rule::unique('cargos', 'nombre')->ignore($this->cargo_id),
            ],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'activo' => ['boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del cargo es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 255 caracteres.',
            'nombre.unique' => 'Ya existe un cargo con ese nombre.',
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
        Gate::authorize('gestionar_organizacion');

        $this->resetValidation();
        $this->resetForm();
        $this->isEditing = false;
        $this->mostrarModal = true;
    }

    public function guardar(): void
    {
        Gate::authorize('gestionar_organizacion');

        $datos = $this->validate();

        Cargo::create($datos);

        $this->cerrarModal();
        $this->resetForm();

        session()->flash('success', 'Cargo registrado correctamente.');
    }

    public function editar($id): void
    {
        Gate::authorize('gestionar_organizacion');

        $cargo = Cargo::findOrFail($id);

        $this->resetValidation();
        $this->cargo_id = $cargo->id;
        $this->nombre = $cargo->nombre;
        $this->descripcion = $cargo->descripcion ?? '';
        $this->activo = (bool) $cargo->activo;
        $this->isEditing = true;
        $this->mostrarModal = true;
    }

    public function actualizar(): void
    {
        Gate::authorize('gestionar_organizacion');

        if (! $this->cargo_id) {
            return;
        }

        $cargo = Cargo::findOrFail($this->cargo_id);
        $datos = $this->validate();

        $cargo->update($datos);

        $this->cerrarModal();
        $this->resetForm();

        session()->flash('success', 'Cargo actualizado correctamente.');
    }

    public function eliminar($id): void
    {
        Gate::authorize('gestionar_organizacion');

        $cargo = Cargo::findOrFail($id);

        if ($cargo->empleados()->exists()) {
            session()->flash('error', 'No se puede eliminar el cargo porque tiene empleados asociados.');

            return;
        }

        $cargo->delete();

        if ((int) $this->cargo_id === (int) $id) {
            $this->cerrarModal();
            $this->resetForm();
        }

        session()->flash('success', 'Cargo eliminado correctamente.');
    }

    public function cerrarModal(): void
    {
        $this->mostrarModal = false;
        $this->resetValidation();
    }

    private function resetForm(): void
    {
        $this->reset([
            'cargo_id',
            'nombre',
            'descripcion',
            'activo',
            'isEditing',
        ]);

        $this->activo = true;
    }

    protected function listar()
    {
        return Cargo::query()
            ->withCount('empleados')
            ->when($this->search !== '', function ($query) {
                $texto = trim($this->search);
                $termino = '%'.mb_strtolower($texto).'%';

                $query->where(function ($query) use ($texto, $termino) {
                    $query->whereRaw('LOWER(nombre) LIKE ?', [$termino])
                        ->orWhereRaw('LOWER(COALESCE(descripcion, \'\')) LIKE ?', [$termino]);

                    // Permite buscar por el código mostrado, por ejemplo CAR-0001.
                    if (preg_match('/^car-?0*(\d+)$/i', $texto, $coincidencia)) {
                        $query->orWhere('id', (int) $coincidencia[1]);
                    }
                });
            })
            ->orderBy('nombre')
            ->paginate(10);
    }

    public function render()
    {
        return view('livewire.gestion-cargos', [
            'cargos' => $this->listar(),
        ]);
    }
}
