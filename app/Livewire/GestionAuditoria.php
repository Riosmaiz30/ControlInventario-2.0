<?php

namespace App\Livewire;

use App\Models\Auditoria;
use App\Models\Configuracion;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;

class GestionAuditoria extends Component
{
    /** Campos del formulario de clave. */
    public $clave_actual = '';

    public $nueva_clave = '';

    public $nueva_clave_confirmation = '';

    /**
     * Reglas de validación para establecer o cambiar la clave.
     */
    protected function rules(): array
    {
        return [
            'clave_actual' => [
                Rule::requiredIf(fn () => Configuracion::tieneClaveAdministrador()),
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    if (
                        Configuracion::tieneClaveAdministrador()
                        && filled($value)
                        && ! Configuracion::verificarClaveAdministrador((string) $value)
                    ) {
                        $fail('La clave actual no es correcta.');
                    }
                },
            ],
            'nueva_clave' => ['required', 'string', 'min:6', 'max:100', 'confirmed'],
        ];
    }

    /**
     * Mensajes de validación en español.
     */
    protected function messages(): array
    {
        return [
            'clave_actual.required' => 'Debe ingresar la clave actual.',
            'clave_actual.string' => 'La clave actual no es válida.',
            'nueva_clave.required' => 'Debe ingresar la nueva clave.',
            'nueva_clave.min' => 'La nueva clave debe tener al menos 6 caracteres.',
            'nueva_clave.max' => 'La nueva clave no puede superar los 100 caracteres.',
            'nueva_clave.confirmed' => 'La confirmación de la clave no coincide.',
        ];
    }

    /**
     * Nombres legibles de los campos.
     */
    protected function validationAttributes(): array
    {
        return [
            'clave_actual' => 'clave actual',
            'nueva_clave' => 'nueva clave',
            'nueva_clave_confirmation' => 'confirmación de la clave',
        ];
    }

    /**
     * Guarda o cambia la clave de administrador.
     */
    public function guardar(): void
    {
        Gate::authorize('ver_auditoria');

        $datos = $this->validate();

        Configuracion::establecer(
            Configuracion::CLAVE_ADMINISTRADOR,
            Hash::make($datos['nueva_clave']),
        );

        $this->reset(['clave_actual', 'nueva_clave', 'nueva_clave_confirmation']);
        $this->resetValidation();

        session()->flash('success', 'Clave de administrador actualizada correctamente.');
    }

    /**
     * Muestra el estado de la clave y los últimos cambios registrados.
     */
    public function render()
    {
        return view('livewire.gestion-auditoria', [
            'tieneClave' => Configuracion::tieneClaveAdministrador(),
            'auditorias' => Auditoria::query()
                ->with('user')
                ->latest()
                ->limit(15)
                ->get(),
        ]);
    }
}
