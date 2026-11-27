<?php

namespace Tests\Feature;

use App\Livewire\GestionEmpleados;
use App\Models\Cargo;
use App\Models\Empleado;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GestionEmpleadosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->administrador()->create());
    }

    private function cargo(string $nombre = 'Administradora'): Cargo
    {
        return Cargo::factory()->create(['nombre' => $nombre]);
    }

    private function empleado(array $datos = []): Empleado
    {
        $cargoId = $datos['cargo_id']
            ?? Cargo::query()->firstOrCreate(['nombre' => 'Administradora'], ['activo' => true])->id;

        return Empleado::create(array_merge([
            'cedula' => 'V-12345678',
            'nombres' => 'Ana',
            'apellidos' => 'Pérez',
            'telefono' => '0414-1234567',
            'cargo_id' => $cargoId,
            'activo' => true,
        ], $datos));
    }

    public function test_crea_un_empleado_con_cedula_y_telefono_formateados(): void
    {
        $cargo = $this->cargo();

        Livewire::test(GestionEmpleados::class)
            ->call('crear')
            ->assertSet('mostrarModal', true)
            ->set('cedula_tipo', 'V')
            ->set('cedula_numero', '23702305')
            ->set('nombres', 'Ana')
            ->set('apellidos', 'Pérez')
            ->set('telefono_prefijo', '0424')
            ->set('telefono_numero', '1236549')
            ->set('cargo_id', $cargo->id)
            ->set('activo', true)
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSee('Empleado registrado correctamente.');

        $this->assertDatabaseHas('empleados', [
            'cedula' => 'V-23702305',
            'nombres' => 'Ana',
            'apellidos' => 'Pérez',
            'telefono' => '0424-1236549',
            'cargo_id' => $cargo->id,
            'activo' => true,
        ]);
    }

    public function test_guarda_telefono_nulo_cuando_no_se_indica_numero(): void
    {
        $cargo = $this->cargo();

        Livewire::test(GestionEmpleados::class)
            ->call('crear')
            ->set('cedula_numero', '12345678')
            ->set('nombres', 'Ana')
            ->set('apellidos', 'Pérez')
            ->set('telefono_numero', '')
            ->set('cargo_id', $cargo->id)
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('empleados', [
            'cedula' => 'V-12345678',
            'telefono' => null,
        ]);
    }

    public function test_no_permite_cedula_duplicada(): void
    {
        $empleado = $this->empleado();

        Livewire::test(GestionEmpleados::class)
            ->call('crear')
            ->set('cedula_tipo', 'V')
            ->set('cedula_numero', '12345678')
            ->set('nombres', 'Otro')
            ->set('apellidos', 'Empleado')
            ->set('cargo_id', $empleado->cargo_id)
            ->call('guardar')
            ->assertHasErrors(['cedula_numero']);

        $this->assertDatabaseCount('empleados', 1);
    }

    public function test_valida_campos_obligatorios(): void
    {
        Livewire::test(GestionEmpleados::class)
            ->call('crear')
            ->call('guardar')
            ->assertHasErrors(['cedula_numero', 'nombres', 'apellidos', 'cargo_id']);
    }

    public function test_valida_formato_de_cedula_y_telefono(): void
    {
        $cargo = $this->cargo();

        Livewire::test(GestionEmpleados::class)
            ->call('crear')
            ->set('cedula_numero', 'ABC')
            ->set('nombres', 'Ana')
            ->set('apellidos', 'Pérez')
            ->set('telefono_numero', '123')
            ->set('cargo_id', $cargo->id)
            ->call('guardar')
            ->assertHasErrors(['cedula_numero', 'telefono_numero']);
    }

    public function test_edita_y_actualiza_un_empleado(): void
    {
        $empleado = $this->empleado();
        $nuevoCargo = $this->cargo('Gerente');

        Livewire::test(GestionEmpleados::class)
            ->call('editar', $empleado->id)
            ->assertSet('isEditing', true)
            ->assertSet('cedula_tipo', 'V')
            ->assertSet('cedula_numero', '12345678')
            ->assertSet('telefono_prefijo', '0414')
            ->assertSet('telefono_numero', '1234567')
            ->assertSet('cargo_id', $empleado->cargo_id)
            ->set('nombres', 'Ana María')
            ->set('cargo_id', $nuevoCargo->id)
            ->set('activo', false)
            ->call('actualizar')
            ->assertHasNoErrors()
            ->assertSee('Empleado actualizado correctamente.');

        $this->assertDatabaseHas('empleados', [
            'id' => $empleado->id,
            'cedula' => 'V-12345678',
            'nombres' => 'Ana María',
            'telefono' => '0414-1234567',
            'cargo_id' => $nuevoCargo->id,
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
            'cargo_id' => $this->cargo('Almacenista')->id,
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

    public function test_crea_un_cargo_desde_el_formulario_de_empleado(): void
    {
        Livewire::test(GestionEmpleados::class)
            ->call('crear')
            ->call('crearCargo')
            ->assertSet('mostrarModalCargo', true)
            ->set('nuevoCargoNombre', 'Jefe de Turno')
            ->set('nuevoCargoDescripcion', 'Coordina el turno de despacho')
            ->call('guardarCargo')
            ->assertHasNoErrors()
            ->assertSet('mostrarModalCargo', false)
            ->assertSee('Cargo registrado correctamente.')
            ->assertSee('Jefe de Turno');

        $cargo = Cargo::query()->where('nombre', 'Jefe de Turno')->first();

        $this->assertNotNull($cargo);
        $this->assertSame('Coordina el turno de despacho', $cargo->descripcion);
        $this->assertTrue($cargo->activo);
    }

    public function test_registra_un_empleado_con_un_cargo_creado_en_el_momento(): void
    {
        Livewire::test(GestionEmpleados::class)
            ->call('crear')
            ->set('cedula_numero', '23702305')
            ->set('nombres', 'Ana')
            ->set('apellidos', 'Pérez')
            ->set('telefono_prefijo', '0424')
            ->set('telefono_numero', '1236549')
            ->call('crearCargo')
            ->set('nuevoCargoNombre', 'Jefe de Turno')
            ->call('guardarCargo')
            ->assertHasNoErrors()
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSee('Empleado registrado correctamente.');

        $cargoId = Cargo::query()->where('nombre', 'Jefe de Turno')->value('id');

        $this->assertDatabaseHas('empleados', [
            'cedula' => 'V-23702305',
            'telefono' => '0424-1236549',
            'cargo_id' => $cargoId,
        ]);
    }

    public function test_selecciona_el_cargo_recien_creado_en_el_formulario(): void
    {
        Livewire::test(GestionEmpleados::class)
            ->call('crear')
            ->call('crearCargo')
            ->set('nuevoCargoNombre', 'Jefe de Turno')
            ->call('guardarCargo')
            ->assertSet('cargo_id', Cargo::query()->where('nombre', 'Jefe de Turno')->value('id'));
    }

    public function test_no_permite_nombre_de_cargo_duplicado_en_alta_rapida(): void
    {
        $this->cargo('Jefe de Turno');

        Livewire::test(GestionEmpleados::class)
            ->call('crear')
            ->call('crearCargo')
            ->set('nuevoCargoNombre', 'Jefe de Turno')
            ->call('guardarCargo')
            ->assertHasErrors(['nuevoCargoNombre' => 'unique']);

        $this->assertDatabaseCount('cargos', 1);
    }
}
