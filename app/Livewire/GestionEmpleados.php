<?php

namespace App\Livewire;

use App\Models\Cargo;
use App\Models\Empleado;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class GestionEmpleados extends Component
{
    use WithPagination;

    public const CEDULA_PREFIJOS = ['V', 'E', 'J', 'G', 'P'];

    public const TELEFONO_PREFIJOS = ['0412', '0414', '0416', '0424', '0426'];

    public string $search = '';

    public $empleado_id = null;

    public string $cedula_tipo = 'V';

    public string $cedula_numero = '';

    public string $nombres = '';

    public string $apellidos = '';

    public string $telefono_prefijo = '0414';

    public string $telefono_numero = '';

    public $cargo_id = '';

    public bool $activo = true;

    public bool $isEditing = false;

    public bool $mostrarModal = false;

    /** Alta rápida de cargos desde el formulario de empleados. */
    public bool $mostrarModalCargo = false;

    public string $nuevoCargoNombre = '';

    public string $nuevoCargoDescripcion = '';

    protected function rules(): array
    {
        return [
            'cedula_tipo' => ['required', Rule::in(self::CEDULA_PREFIJOS)],
            'cedula_numero' => [
                'required',
                'bail',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! preg_match('/^\d{5,9}$/', (string) $value)) {
                        $fail('La cédula debe contener entre 5 y 9 dígitos.');

                        return;
                    }

                    $existe = Empleado::query()
                        ->where('cedula', $this->cedulaCompleta())
                        ->when($this->empleado_id, fn (Builder $query) => $query->whereKeyNot($this->empleado_id))
                        ->exists();

                    if ($existe) {
                        $fail('Ya existe un empleado con esa cédula.');
                    }
                },
            ],
            'nombres' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],
            'telefono_prefijo' => ['required', 'regex:/^0\d{3}$/'],
            'telefono_numero' => [
                'nullable',
                'bail',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (filled($value) && ! preg_match('/^\d{7}$/', (string) $value)) {
                        $fail('El teléfono debe contener exactamente 7 dígitos.');
                    }
                },
            ],
            'cargo_id' => ['required', 'integer', Rule::exists('cargos', 'id')],
            'activo' => ['boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            'cedula_tipo.required' => 'El tipo de cédula es obligatorio.',
            'cedula_tipo.in' => 'El tipo de cédula no es válido.',
            'cedula_numero.required' => 'La cédula es obligatoria.',
            'nombres.required' => 'Los nombres son obligatorios.',
            'nombres.max' => 'Los nombres no pueden superar los 255 caracteres.',
            'apellidos.required' => 'Los apellidos son obligatorios.',
            'apellidos.max' => 'Los apellidos no pueden superar los 255 caracteres.',
            'telefono_prefijo.required' => 'El prefijo telefónico es obligatorio.',
            'telefono_prefijo.regex' => 'El prefijo telefónico no es válido.',
            'cargo_id.required' => 'El cargo es obligatorio.',
            'cargo_id.integer' => 'El cargo seleccionado no es válido.',
            'cargo_id.exists' => 'El cargo seleccionado no existe.',
            'activo.boolean' => 'El estado activo no es válido.',
            'nuevoCargoNombre.required' => 'El nombre del cargo es obligatorio.',
            'nuevoCargoNombre.max' => 'El nombre del cargo no puede superar los 255 caracteres.',
            'nuevoCargoNombre.unique' => 'Ya existe un cargo con ese nombre.',
            'nuevoCargoDescripcion.max' => 'La descripción no puede superar los 1000 caracteres.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'cedula_tipo' => 'tipo de cédula',
            'cedula_numero' => 'cédula',
            'nombres' => 'nombres',
            'apellidos' => 'apellidos',
            'telefono_prefijo' => 'prefijo telefónico',
            'telefono_numero' => 'teléfono',
            'cargo_id' => 'cargo',
            'activo' => 'estado',
            'nuevoCargoNombre' => 'nombre del cargo',
            'nuevoCargoDescripcion' => 'descripción del cargo',
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

        $this->validate();

        Empleado::create([
            'cedula' => $this->cedulaCompleta(),
            'nombres' => $this->nombres,
            'apellidos' => $this->apellidos,
            'telefono' => $this->telefonoCompleto(),
            'cargo_id' => $this->cargo_id,
            'activo' => $this->activo,
        ]);

        $this->cerrarModal();
        $this->resetForm();

        session()->flash('success', 'Empleado registrado correctamente.');
    }

    public function editar($id): void
    {
        Gate::authorize('gestionar_organizacion');

        $empleado = Empleado::findOrFail($id);

        $this->resetValidation();
        $this->empleado_id = $empleado->id;
        [$this->cedula_tipo, $this->cedula_numero] = $this->separarCedula($empleado->cedula);
        $this->nombres = $empleado->nombres;
        $this->apellidos = $empleado->apellidos;
        [$this->telefono_prefijo, $this->telefono_numero] = $this->separarTelefono($empleado->telefono);
        $this->cargo_id = $empleado->cargo_id;
        $this->activo = (bool) $empleado->activo;
        $this->isEditing = true;
        $this->mostrarModal = true;
    }

    public function actualizar(): void
    {
        Gate::authorize('gestionar_organizacion');

        if (! $this->empleado_id) {
            return;
        }

        $empleado = Empleado::findOrFail($this->empleado_id);
        $this->validate();

        $empleado->update([
            'cedula' => $this->cedulaCompleta(),
            'nombres' => $this->nombres,
            'apellidos' => $this->apellidos,
            'telefono' => $this->telefonoCompleto(),
            'cargo_id' => $this->cargo_id,
            'activo' => $this->activo,
        ]);

        $this->cerrarModal();
        $this->resetForm();

        session()->flash('success', 'Empleado actualizado correctamente.');
    }

    public function eliminar($id): void
    {
        Gate::authorize('gestionar_organizacion');

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

    public function crearCargo(): void
    {
        Gate::authorize('gestionar_organizacion');

        $this->resetValidation(['nuevoCargoNombre', 'nuevoCargoDescripcion']);
        $this->reset(['nuevoCargoNombre', 'nuevoCargoDescripcion']);
        $this->mostrarModalCargo = true;
    }

    public function guardarCargo(): void
    {
        Gate::authorize('gestionar_organizacion');

        $datos = $this->validate([
            'nuevoCargoNombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('cargos', 'nombre'),
            ],
            'nuevoCargoDescripcion' => ['nullable', 'string', 'max:1000'],
        ]);

        $cargo = Cargo::create([
            'nombre' => $datos['nuevoCargoNombre'],
            'descripcion' => $datos['nuevoCargoDescripcion'] ?? null,
            'activo' => true,
        ]);

        $this->cargo_id = $cargo->id;
        $this->cerrarModalCargo();

        session()->flash('success', 'Cargo registrado correctamente.');
    }

    public function cerrarModalCargo(): void
    {
        $this->mostrarModalCargo = false;
        $this->resetValidation(['nuevoCargoNombre', 'nuevoCargoDescripcion']);
        $this->reset(['nuevoCargoNombre', 'nuevoCargoDescripcion']);
    }

    public function cerrarModalConEscape(): void
    {
        if ($this->mostrarModalCargo) {
            $this->cerrarModalCargo();

            return;
        }

        $this->cerrarModal();
    }

    public function cerrarModal(): void
    {
        $this->mostrarModal = false;
        $this->mostrarModalCargo = false;
        $this->resetValidation();
    }

    private function resetForm(): void
    {
        $this->reset([
            'empleado_id',
            'cedula_tipo',
            'cedula_numero',
            'nombres',
            'apellidos',
            'telefono_prefijo',
            'telefono_numero',
            'cargo_id',
            'activo',
            'isEditing',
            'mostrarModalCargo',
            'nuevoCargoNombre',
            'nuevoCargoDescripcion',
        ]);

        $this->cedula_tipo = 'V';
        $this->telefono_prefijo = '0414';
        $this->activo = true;
    }

    private function cedulaCompleta(): string
    {
        return $this->cedula_tipo.'-'.$this->cedula_numero;
    }

    private function telefonoCompleto(): ?string
    {
        if (blank($this->telefono_numero)) {
            return null;
        }

        return $this->telefono_prefijo.'-'.$this->telefono_numero;
    }

    /**
     * Separa una cédula almacenada en tipo y número.
     *
     * @return array{0: string, 1: string}
     */
    private function separarCedula(?string $cedula): array
    {
        $cedula = trim((string) $cedula);

        if (preg_match('/^([VEJGP])-?(\d+)$/i', $cedula, $coincidencia)) {
            return [strtoupper($coincidencia[1]), $coincidencia[2]];
        }

        return ['V', preg_replace('/\D/', '', $cedula)];
    }

    /**
     * Separa un teléfono almacenado en prefijo y número.
     *
     * @return array{0: string, 1: string}
     */
    private function separarTelefono(?string $telefono): array
    {
        $digitos = preg_replace('/\D/', '', (string) $telefono);

        foreach (self::TELEFONO_PREFIJOS as $prefijo) {
            if (str_starts_with($digitos, $prefijo)) {
                return [$prefijo, substr($digitos, strlen($prefijo))];
            }
        }

        if (strlen($digitos) === 11 && str_starts_with($digitos, '0')) {
            return [substr($digitos, 0, 4), substr($digitos, 4)];
        }

        return ['0414', $digitos];
    }

    protected function listar()
    {
        return Empleado::query()
            ->with('cargo')
            ->when($this->search !== '', function ($query) {
                $termino = '%'.mb_strtolower(trim($this->search)).'%';

                $query->where(function ($query) use ($termino) {
                    $query->whereRaw('LOWER(cedula) LIKE ?', [$termino])
                        ->orWhereRaw('LOWER(nombres) LIKE ?', [$termino])
                        ->orWhereRaw('LOWER(apellidos) LIKE ?', [$termino])
                        ->orWhereHas('cargo', fn ($query) => $query->whereRaw('LOWER(nombre) LIKE ?', [$termino]));
                });
            })
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->paginate(10);
    }

    public function render()
    {
        $telefonoPrefijos = self::TELEFONO_PREFIJOS;

        if (filled($this->telefono_prefijo) && ! in_array($this->telefono_prefijo, $telefonoPrefijos, true)) {
            $telefonoPrefijos[] = $this->telefono_prefijo;
        }

        return view('livewire.gestion-empleados', [
            'empleados' => $this->listar(),
            'cargos' => Cargo::query()->orderBy('nombre')->get(),
            'cedulaPrefijos' => self::CEDULA_PREFIJOS,
            'telefonoPrefijos' => $telefonoPrefijos,
        ]);
    }
}
