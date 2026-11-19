<?php

namespace App\Livewire;

use App\Models\Empleado;
use App\Models\Sede;
use Livewire\Component;
use Livewire\WithPagination;

class GestionSedes extends Component
{
    use WithPagination;

    public string $search = '';

    public $sede_id = null;
    public $nombre = '';
    public $tipo = 'consumidor';
    public $empleado_id = '';
    public $direccion = '';
    public $telefono = '';
    public bool $activo = true;

    public bool $isEditing = false;
    public bool $mostrarModal = false;

    protected function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'tipo' => ['required', 'in:principal,consumidor'],
            'empleado_id' => ['nullable', 'integer', 'exists:empleados,id'],
            'direccion' => ['required', 'string', 'max:1000'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'activo' => ['boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la sede es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 255 caracteres.',
            'tipo.required' => 'El tipo de sede es obligatorio.',
            'tipo.in' => 'El tipo de sede debe ser principal o consumidor.',
            'empleado_id.integer' => 'El responsable seleccionado no es válido.',
            'empleado_id.exists' => 'El responsable seleccionado no existe.',
            'direccion.required' => 'La dirección es obligatoria.',
            'direccion.max' => 'La dirección no puede superar los 1000 caracteres.',
            'telefono.max' => 'El teléfono no puede superar los 50 caracteres.',
            'activo.boolean' => 'El estado activo no es válido.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'nombre' => 'nombre',
            'tipo' => 'tipo',
            'empleado_id' => 'responsable',
            'direccion' => 'dirección',
            'telefono' => 'teléfono',
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
        $datos['empleado_id'] = $datos['empleado_id'] ?: null;

        Sede::create($datos);

        $this->cerrarModal();
        $this->resetForm();
        $this->notificarTotalSedes();

        session()->flash('success', 'Sede registrada correctamente.');
    }

    public function editar($id): void
    {
        $sede = Sede::findOrFail($id);

        $this->resetValidation();
        $this->sede_id = $sede->id;
        $this->nombre = $sede->nombre;
        $this->tipo = $sede->tipo;
        $this->empleado_id = $sede->empleado_id ?? '';
        $this->direccion = $sede->direccion;
        $this->telefono = $sede->telefono ?? '';
        $this->activo = (bool) $sede->activo;
        $this->isEditing = true;
        $this->mostrarModal = true;
    }

    public function actualizar(): void
    {
        if (! $this->sede_id) {
            return;
        }

        $sede = Sede::findOrFail($this->sede_id);
        $datos = $this->validate();
        $datos['empleado_id'] = $datos['empleado_id'] ?: null;

        $sede->update($datos);

        $this->cerrarModal();
        $this->resetForm();
        $this->notificarTotalSedes();

        session()->flash('success', 'Sede actualizada correctamente.');
    }

    public function eliminar($id): void
    {
        $sede = Sede::findOrFail($id);
        $sede->delete();

        if ((int) $this->sede_id === (int) $id) {
            $this->cerrarModal();
            $this->resetForm();
        }

        $this->notificarTotalSedes();

        session()->flash('success', 'Sede eliminada correctamente.');
    }

    public function cerrarModal(): void
    {
        $this->mostrarModal = false;
        $this->resetValidation();
    }

    private function notificarTotalSedes(): void
    {
        $this->dispatch('sedes-actualizadas', count: Sede::query()->count());
    }

    private function resetForm(): void
    {
        $this->reset([
            'sede_id',
            'nombre',
            'tipo',
            'empleado_id',
            'direccion',
            'telefono',
            'activo',
            'isEditing',
        ]);

        $this->tipo = 'consumidor';
        $this->activo = true;
    }

    protected function listar()
    {
        return Sede::query()
            ->with('responsable')
            ->when($this->search !== '', function ($query) {
                $termino = '%' . mb_strtolower(trim($this->search)) . '%';

                $query->where(function ($query) use ($termino) {
                    $query->whereRaw('LOWER(nombre) LIKE ?', [$termino])
                        ->orWhereRaw('LOWER(direccion) LIKE ?', [$termino])
                        ->orWhereHas('responsable', function ($query) use ($termino) {
                            $query->whereRaw('LOWER(nombres) LIKE ?', [$termino])
                                ->orWhereRaw('LOWER(apellidos) LIKE ?', [$termino]);
                        });
                });
            })
            ->orderBy('nombre')
            ->paginate(10);
    }

    public function render()
    {
        return view('livewire.gestion-sedes', [
            'sedes' => $this->listar(),
            'empleados' => Empleado::orderBy('nombres')->orderBy('apellidos')->get(),
        ]);
    }
}
