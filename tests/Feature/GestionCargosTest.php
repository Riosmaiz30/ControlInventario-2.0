<?php

namespace Tests\Feature;

use App\Livewire\GestionCargos;
use App\Models\Cargo;
use App\Models\Empleado;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GestionCargosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->administrador()->create());
    }

    public function test_crea_un_cargo(): void
    {
        Livewire::test(GestionCargos::class)
            ->call('crear')
            ->assertSet('mostrarModal', true)
            ->set('nombre', 'Jefe de Almacén')
            ->set('descripcion', 'Responsable del almacén central')
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSee('Cargo registrado correctamente.');

        $this->assertDatabaseHas('cargos', [
            'nombre' => 'Jefe de Almacén',
            'descripcion' => 'Responsable del almacén central',
            'activo' => true,
        ]);
    }

    public function test_no_permite_nombre_duplicado(): void
    {
        Cargo::factory()->create(['nombre' => 'Jefe de Almacén']);

        Livewire::test(GestionCargos::class)
            ->call('crear')
            ->set('nombre', 'Jefe de Almacén')
            ->call('guardar')
            ->assertHasErrors(['nombre' => 'unique']);

        $this->assertDatabaseCount('cargos', 1);
    }

    public function test_valida_campos_obligatorios(): void
    {
        Livewire::test(GestionCargos::class)
            ->call('crear')
            ->call('guardar')
            ->assertHasErrors(['nombre' => 'required']);
    }

    public function test_edita_y_actualiza_un_cargo(): void
    {
        $cargo = Cargo::factory()->create(['nombre' => 'Jefe de Almacén']);

        Livewire::test(GestionCargos::class)
            ->call('editar', $cargo->id)
            ->assertSet('isEditing', true)
            ->assertSet('nombre', 'Jefe de Almacén')
            ->set('nombre', 'Jefe de Almacén Central')
            ->set('activo', false)
            ->call('actualizar')
            ->assertHasNoErrors()
            ->assertSee('Cargo actualizado correctamente.');

        $this->assertDatabaseHas('cargos', [
            'id' => $cargo->id,
            'nombre' => 'Jefe de Almacén Central',
            'activo' => false,
        ]);
    }

    public function test_elimina_un_cargo_sin_empleados(): void
    {
        $cargo = Cargo::factory()->create();

        Livewire::test(GestionCargos::class)
            ->call('eliminar', $cargo->id)
            ->assertSee('Cargo eliminado correctamente.');

        $this->assertDatabaseMissing('cargos', ['id' => $cargo->id]);
    }

    public function test_no_elimina_un_cargo_con_empleados_asociados(): void
    {
        $cargo = Cargo::factory()->create();

        Empleado::create([
            'cedula' => 'V-12345678',
            'nombres' => 'Ana',
            'apellidos' => 'Pérez',
            'telefono' => '0414-1234567',
            'cargo_id' => $cargo->id,
            'activo' => true,
        ]);

        Livewire::test(GestionCargos::class)
            ->call('eliminar', $cargo->id)
            ->assertSee('No se puede eliminar el cargo porque tiene empleados asociados.');

        $this->assertDatabaseHas('cargos', ['id' => $cargo->id]);
    }

    public function test_busca_por_nombre_o_codigo(): void
    {
        $almacen = Cargo::factory()->create(['nombre' => 'Jefe de Almacén']);
        Cargo::factory()->create(['nombre' => 'Jefe de Centro']);

        Livewire::test(GestionCargos::class)
            ->set('search', 'Almacén')
            ->assertSee('Jefe de Almacén')
            ->assertDontSee('Jefe de Centro')
            ->set('search', $almacen->codigo)
            ->assertSee('Jefe de Almacén')
            ->assertDontSee('Jefe de Centro');
    }
}
