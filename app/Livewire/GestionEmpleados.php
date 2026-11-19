<?php

namespace App\Livewire;

use App\Models\Empleado;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class GestionEmpleados extends Component
{
    use WithPagination;

    public string $search = '';

    public $empleado_id = null;
    public $cedula = '';
    public $nombres = '';
    public $apellidos = '';
    public $telefono = '';
    public $cargo = '';
    public bool $activo = true;

    public bool $isEditing = false;
    public bool $mostrarModal = false;

    protected function rules(): array
    {
        return [
            'cedula' => [
                'required',
                'string',
                'max:50',
                Rule::unique('empleados', 'cedula')->ignore($this->empleado_id),
            ],
            'nombres' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'cargo' => ['required', 'string', 'max:255'],
            'activo' => ['boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            'cedula.required' => 'La cédula es obligatoria.',
            'cedula.max' => 'La cédula no puede superar los 50 caracteres.',
            'cedula.unique' => 'Ya existe un empleado con esa cédula.',
            'nombres.required' => 'Los nombres son obligatorios.',
            'nombres.max' => 'Los nombres no pueden superar los 255 caracteres.',
            'apellidos.required' => 'Los apellidos son obligatorios.',
            'apellidos.max' => 'Los apellidos no pueden superar los 255 caracteres.',
            'telefono.max' => 'El teléfono no puede superar los 50 caracteres.',
            'cargo.required' => 'El cargo es obligatorio.',
            'cargo.max' => 'El cargo no puede superar los 255 caracteres.',
            'activo.boolean' => 'El estado activo no es válido.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'cedula' => 'cédula',
            'nombres' => 'nombres',
            'apellidos' => 'apellidos',
            'telefono' => 'teléfono',
            'cargo' => 'cargo',
            'activo' => 'estado',
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function crear(): void
    {
        $this->resetValidation();
        $this->resetForm();
        $this->isEditing = false;
        $this->mostrarModal = true;
    }

    public function guardar(): void
    {
        $datos = $this->validate();

        Empleado::create($datos);

        $this->cerrarModal();
        $this->resetForm();

        session()->flash('success', 'Empleado registrado correctamente.');
    }

    public function editar($id): void
    {
        $empleado = Empleado::findOrFail($id);

        $this->resetValidation();
        $this->empleado_id = $empleado->id;
        $this->cedula = $empleado->cedula;
        $this->nombres = $empleado->nombres;
        $this->apellidos = $empleado->apellidos;
        $this->telefono = $empleado->telefono ?? '';
        $this->cargo = $empleado->cargo;
        $this->activo = (bool) $empleado->activo;
        $this->isEditing = true;
        $this->mostrarModal = true;
    }

    public function actualizar(): void
    {
        if (! $this->empleado_id) {
            return;
        }

        $empleado = Empleado::findOrFail($this->empleado_id);
        $datos = $this->validate();

        $empleado->update($datos);

        $this->cerrarModal();
        $this->resetForm();

        session()->flash('success', 'Empleado actualizado correctamente.');
    }

    public function eliminar($id): void
    {
        $empleado = Empleado::findOrFail($id);

        if ($empleado->sedeACargo()->exists()) {
            session()->flash('error', 'No se puede eliminar el empleado porque está asignado como responsable de una sede.');

            return;
        }

        $empleado->delete();

        if ((int) $this->empleado_id === (int) $id) {
            $this->cerrarModal();
            $this->resetForm();
        }

        session()->flash('success', 'Empleado eliminado correctamente.');
    }

    public function cerrarModal(): void
    {
        $this->mostrarModal = false;
        $this->resetValidation();
    }

    private function resetForm(): void
    {
        $this->reset([
            'empleado_id',
            'cedula',
            'nombres',
            'apellidos',
            'telefono',
            'cargo',
            'activo',
            'isEditing',
        ]);

        $this->activo = true;
    }

    protected function listar()
    {
        return Empleado::query()
            ->when($this->search !== '', function ($query) {
                $termino = '%' . mb_strtolower(trim($this->search)) . '%';

                $query->where(function ($query) use ($termino) {
                    $query->whereRaw('LOWER(cedula) LIKE ?', [$termino])
                        ->orWhereRaw('LOWER(nombres) LIKE ?', [$termino])
                        ->orWhereRaw('LOWER(apellidos) LIKE ?', [$termino])
                        ->orWhereRaw('LOWER(cargo) LIKE ?', [$termino]);
                });
            })
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->paginate(10);
    }

    public function render()
    {
        return view('livewire.gestion-empleados', [
            'empleados' => $this->listar(),
        ]);
    }
}
