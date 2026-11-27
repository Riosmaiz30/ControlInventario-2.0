<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class GestionUsuarios extends Component
{
    use WithPagination;

    /**
     * Etiquetas legibles de los roles disponibles.
     */
    public const ROLES = [
        User::ROLE_ADMINISTRADOR => 'Administrador',
        User::ROLE_JEFE_ALMACEN => 'Jefe de Almacén',
        User::ROLE_AYUDANTE_ALMACEN => 'Ayudante de Almacén',
    ];

    public string $search = '';

    public $user_id = null;

    public string $name = '';

    public string $email = '';

    public string $role = User::ROLE_AYUDANTE_ALMACEN;

    public string $password = '';

    public string $password_confirmation = '';

    public bool $isEditing = false;

    public bool $mostrarModal = false;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user_id),
            ],
            'role' => ['required', 'string', Rule::in(array_keys(self::ROLES))],
            'password' => [Rule::requiredIf(! $this->isEditing), 'nullable', 'string', 'min:8', 'confirmed'],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'El nombre del usuario es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no es válido.',
            'email.unique' => 'Ya existe un usuario con ese correo electrónico.',
            'role.required' => 'Debe seleccionar un rol.',
            'role.in' => 'El rol seleccionado no es válido.',
            'password.required' => 'La contraseña es obligatoria para crear un usuario.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'name' => 'nombre',
            'email' => 'correo electrónico',
            'role' => 'rol',
            'password' => 'contraseña',
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function crear(): void
    {
        Gate::authorize('gestionar_usuarios');

        $this->resetValidation();
        $this->resetForm();
        $this->mostrarModal = true;
    }

    public function editar($id): void
    {
        Gate::authorize('gestionar_usuarios');

        $usuario = User::findOrFail($id);

        $this->resetValidation();
        $this->user_id = $usuario->id;
        $this->name = $usuario->name;
        $this->email = $usuario->email;
        $this->role = $usuario->role;
        $this->isEditing = true;
        $this->mostrarModal = true;
    }

    public function guardar(): void
    {
        Gate::authorize('gestionar_usuarios');

        $datos = $this->validate();

        User::create([
            'name' => $datos['name'],
            'email' => $datos['email'],
            'role' => $datos['role'],
            'password' => Hash::make($datos['password']),
        ]);

        $this->cerrarModal();

        session()->flash('success', 'Usuario registrado correctamente.');
    }

    public function actualizar(): void
    {
        Gate::authorize('gestionar_usuarios');

        $usuario = User::findOrFail($this->user_id);
        $datos = $this->validate();

        if (
            $usuario->role === User::ROLE_ADMINISTRADOR
            && $datos['role'] !== User::ROLE_ADMINISTRADOR
            && User::query()->where('role', User::ROLE_ADMINISTRADOR)->count() <= 1
        ) {
            session()->flash('error', 'Debe existir al menos un administrador en el sistema.');

            return;
        }

        if ((int) $usuario->id === (int) auth()->id() && $datos['role'] !== User::ROLE_ADMINISTRADOR) {
            session()->flash('error', 'No puedes cambiar tu propio rol de administrador.');

            return;
        }

        $cambios = [
            'name' => $datos['name'],
            'email' => $datos['email'],
            'role' => $datos['role'],
        ];

        if (filled($datos['password'])) {
            $cambios['password'] = Hash::make($datos['password']);
        }

        $usuario->update($cambios);

        $this->cerrarModal();

        session()->flash('success', 'Usuario actualizado correctamente.');
    }

    public function cerrarModal(): void
    {
        $this->mostrarModal = false;
        $this->resetValidation();
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset([
            'user_id',
            'name',
            'email',
            'role',
            'password',
            'password_confirmation',
            'isEditing',
        ]);

        $this->role = User::ROLE_AYUDANTE_ALMACEN;
    }

    protected function listar()
    {
        return User::query()
            ->when($this->search !== '', function ($query) {
                $termino = '%'.mb_strtolower(trim($this->search)).'%';

                $query->where(function ($query) use ($termino) {
                    $query->whereRaw('LOWER(name) LIKE ?', [$termino])
                        ->orWhereRaw('LOWER(email) LIKE ?', [$termino]);
                });
            })
            ->orderBy('name')
            ->paginate(10);
    }

    public function render()
    {
        return view('livewire.gestion-usuarios', [
            'usuarios' => $this->listar(),
            'roles' => self::ROLES,
        ]);
    }
}
