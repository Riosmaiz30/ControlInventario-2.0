<?php

namespace App\Livewire;

use App\Models\Proveedor;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class GestionProveedores extends Component
{
    use WithPagination;

    public string $search = '';

    public $proveedor_id = null;
    public $nombre = '';
    public $rif_nit = '';
    public $telefono = '';
    public $correo = '';
    public $direccion = '';

    public bool $isEditing = false;
    public bool $mostrarModal = false;

    protected function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'rif_nit' => [
                'required',
                'string',
                'max:255',
                Rule::unique('proveedores', 'rif')->ignore($this->proveedor_id),
            ],
            'telefono' => ['nullable', 'string', 'max:50'],
            'correo' => ['nullable', 'email', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del proveedor es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 255 caracteres.',
            'rif_nit.required' => 'El RIF/NIT es obligatorio.',
            'rif_nit.max' => 'El RIF/NIT no puede superar los 255 caracteres.',
            'rif_nit.unique' => 'Ya existe un proveedor con ese RIF/NIT.',
            'telefono.max' => 'El teléfono no puede superar los 50 caracteres.',
            'correo.email' => 'El correo electrónico no es válido.',
            'correo.max' => 'El correo no puede superar los 255 caracteres.',
            'direccion.max' => 'La dirección no puede superar los 1000 caracteres.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'nombre' => 'nombre',
            'rif_nit' => 'RIF/NIT',
            'telefono' => 'teléfono',
            'correo' => 'correo',
            'direccion' => 'dirección',
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
        $datos['rif'] = $datos['rif_nit'];
        unset($datos['rif_nit']);

        Proveedor::create($datos);

        $this->cerrarModal();
        $this->resetForm();

        session()->flash('success', 'Proveedor registrado correctamente.');
    }

    public function editar($id): void
    {
        $proveedor = Proveedor::findOrFail($id);

        $this->resetValidation();
        $this->proveedor_id = $proveedor->id;
        $this->nombre = $proveedor->nombre;
        $this->rif_nit = $proveedor->rif;
        $this->telefono = $proveedor->telefono ?? '';
        $this->correo = $proveedor->correo ?? '';
        $this->direccion = $proveedor->direccion ?? '';
        $this->isEditing = true;
        $this->mostrarModal = true;
    }

    public function actualizar(): void
    {
        if (! $this->proveedor_id) {
            return;
        }

        $proveedor = Proveedor::findOrFail($this->proveedor_id);
        $datos = $this->validate();
        $datos['rif'] = $datos['rif_nit'];
        unset($datos['rif_nit']);

        $proveedor->update($datos);

        $this->cerrarModal();
        $this->resetForm();

        session()->flash('success', 'Proveedor actualizado correctamente.');
    }

    public function eliminar($id): void
    {
        $proveedor = Proveedor::findOrFail($id);

        if ($proveedor->lotes()->exists()) {
            session()->flash('error', 'No se puede eliminar el proveedor porque tiene lotes asociados.');

            return;
        }

        $proveedor->delete();

        if ((int) $this->proveedor_id === (int) $id) {
            $this->cerrarModal();
            $this->resetForm();
        }

        session()->flash('success', 'Proveedor eliminado correctamente.');
    }

    public function cerrarModal(): void
    {
        $this->mostrarModal = false;
        $this->resetValidation();
    }

    private function resetForm(): void
    {
        $this->reset([
            'proveedor_id',
            'nombre',
            'rif_nit',
            'telefono',
            'correo',
            'direccion',
            'isEditing',
        ]);
    }

    protected function listar()
    {
        return Proveedor::query()
            ->when($this->search !== '', function ($query) {
                $termino = '%' . mb_strtolower(trim($this->search)) . '%';

                $query->where(function ($query) use ($termino) {
                    $query->whereRaw('LOWER(nombre) LIKE ?', [$termino])
                        ->orWhereRaw('LOWER(codigo) LIKE ?', [$termino])
                        ->orWhereRaw('LOWER(rif) LIKE ?', [$termino]);
                });
            })
            ->orderBy('nombre')
            ->paginate(10);
    }

    public function render()
    {
        return view('livewire.gestion-proveedores', [
            'proveedores' => $this->listar(),
        ]);
    }
}
