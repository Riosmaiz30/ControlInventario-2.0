<?php

namespace Tests\Feature;

use App\Livewire\GestionEmpleados;
use App\Models\Empleado;
use App\Models\Sede;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GestionEmpleadosTest extends TestCase
{
    use RefreshDatabase;

    private function empleado(array $datos = []): Empleado
    {
        return Empleado::create(array_merge([
            'cedula' => 'V-12345678',
            'nombres' => 'Ana',
            'apellidos' => 'Pérez',
            'telefono' => '0414-1234567',
            'cargo' => 'Administradora',
            'activo' => true,
        ], $datos));
    }

    public function test_crea_un_empleado(): void
    {
        Livewire::test(GestionEmpleados::class)
            ->call('crear')
            ->assertSet('mostrarModal', true)
            ->set('cedula', 'V-12345678')
            ->set('nombres', 'Ana')
            ->set('apellidos', 'Pérez')
            ->set('telefono', '0414-1234567')
            ->set('cargo', 'Administradora')
            ->set('activo', true)
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSee('Empleado registrado correctamente.');

        $this->assertDatabaseHas('empleados', [
            'cedula' => 'V-12345678',
            'nombres' => 'Ana',
            'apellidos' => 'Pérez',
            'cargo' => 'Administradora',
            'activo' => true,
        ]);
    }

    public function test_no_permite_cedula_duplicada(): void
    {
        $this->empleado();

        Livewire::test(GestionEmpleados::class)
            ->call('crear')
            ->set('cedula', 'V-12345678')
            ->set('nombres', 'Otro')
            ->set('apellidos', 'Empleado')
            ->set('cargo', 'Encargado')
            ->call('guardar')
            ->assertHasErrors(['cedula' => 'unique']);

        $this->assertDatabaseCount('empleados', 1);
    }

    public function test_valida_campos_obligatorios(): void
    {
        Livewire::test(GestionEmpleados::class)
            ->call('crear')
            ->call('guardar')
            ->assertHasErrors(['cedula', 'nombres', 'apellidos', 'cargo']);
    }

    public function test_edita_y_actualiza_un_empleado(): void
    {
        $empleado = $this->empleado();

        Livewire::test(GestionEmpleados::class)
            ->call('editar', $empleado->id)
            ->assertSet('isEditing', true)
            ->assertSet('cedula', 'V-12345678')
            ->set('nombres', 'Ana María')
            ->set('cargo', 'Gerente')
            ->set('activo', false)
            ->call('actualizar')
            ->assertHasNoErrors()
            ->assertSee('Empleado actualizado correctamente.');

        $this->assertDatabaseHas('empleados', [
            'id' => $empleado->id,
            'nombres' => 'Ana María',
            'cargo' => 'Gerente',
            'activo' => false,
        ]);
    }

    public function test_elimina_un_empleado_sin_sede_asignada(): void
    {
        $empleado = $this->empleado();

        Livewire::test(GestionEmpleados::class)
            ->call('eliminar', $empleado->id)
            ->assertSee('Empleado eliminado correctamente.');

        $this->assertDatabaseMissing('empleados', ['id' => $empleado->id]);
    }

    public function test_no_elimina_un_empleado_asignado_a_una_sede(): void
    {
        $empleado = $this->empleado();

        Sede::create([
            'nombre' => 'Sede Principal',
            'tipo' => 'principal',
            'empleado_id' => $empleado->id,
            'direccion' => 'Av. Principal',
            'telefono' => '0212-1234567',
            'activo' => true,
        ]);

        Livewire::test(GestionEmpleados::class)
            ->call('eliminar', $empleado->id)
            ->assertSee('No se puede eliminar el empleado porque está asignado como responsable de una sede.');

        $this->assertDatabaseHas('empleados', ['id' => $empleado->id]);
    }

    public function test_busca_por_cedula_nombre_apellido_o_cargo(): void
    {
        $this->empleado();
        $this->empleado([
            'cedula' => 'V-87654321',
            'nombres' => 'Luis',
            'apellidos' => 'Gómez',
            'cargo' => 'Almacenista',
        ]);

        Livewire::test(GestionEmpleados::class)
            ->set('search', 'V-12345678')
            ->assertSee('Ana')
            ->assertDontSee('Luis')
            ->set('search', 'Gómez')
            ->assertSee('Luis')
            ->assertDontSee('Ana')
            ->set('search', 'Almacenista')
            ->assertSee('Luis')
            ->assertDontSee('Ana');
    }
}
