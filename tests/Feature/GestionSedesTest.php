<?php

namespace Tests\Feature;

use App\Livewire\GestionSedes;
use App\Models\Despacho;
use App\Models\Empleado;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GestionSedesTest extends TestCase
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

    private function sede(array $datos = []): Sede
    {
        return Sede::create(array_merge([
            'nombre' => 'Sede Principal',
            'tipo' => 'principal',
            'empleado_id' => null,
            'direccion' => 'Av. Principal',
            'telefono' => '0212-1234567',
            'activo' => true,
        ], $datos));
    }

    public function test_crea_una_sede_con_responsable(): void
    {
        $empleado = $this->empleado();

        Livewire::test(GestionSedes::class)
            ->call('crear')
            ->assertSet('mostrarModal', true)
            ->set('nombre', 'Sede Principal')
            ->set('tipo', 'principal')
            ->set('empleado_id', $empleado->id)
            ->set('direccion', 'Av. Principal')
            ->set('telefono', '0212-1234567')
            ->set('activo', true)
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertDispatched('sedes-actualizadas', count: 1)
            ->assertSee('Sede registrada correctamente.');

        $this->assertDatabaseHas('sedes', [
            'nombre' => 'Sede Principal',
            'tipo' => 'principal',
            'empleado_id' => $empleado->id,
            'direccion' => 'Av. Principal',
            'activo' => true,
        ]);
    }

    public function test_crea_una_sede_sin_responsable(): void
    {
        Livewire::test(GestionSedes::class)
            ->call('crear')
            ->set('nombre', 'Sede Consumidora')
            ->set('tipo', 'consumidor')
            ->set('empleado_id', '')
            ->set('direccion', 'Calle 5')
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSee('Sede registrada correctamente.');

        $this->assertDatabaseHas('sedes', [
            'nombre' => 'Sede Consumidora',
            'tipo' => 'consumidor',
            'empleado_id' => null,
        ]);
    }

    public function test_valida_tipo_y_campos_obligatorios(): void
    {
        Livewire::test(GestionSedes::class)
            ->call('crear')
            ->set('nombre', '')
            ->set('tipo', 'invalido')
            ->set('direccion', '')
            ->call('guardar')
            ->assertHasErrors(['nombre', 'tipo', 'direccion']);
    }

    public function test_edita_y_actualiza_una_sede(): void
    {
        $empleado = $this->empleado();
        $sede = $this->sede();

        Livewire::test(GestionSedes::class)
            ->call('editar', $sede->id)
            ->assertSet('isEditing', true)
            ->assertSet('nombre', 'Sede Principal')
            ->set('nombre', 'Sede Central')
            ->set('tipo', 'consumidor')
            ->set('empleado_id', $empleado->id)
            ->set('direccion', 'Av. Bolívar')
            ->set('activo', false)
            ->call('actualizar')
            ->assertHasNoErrors()
            ->assertDispatched('sedes-actualizadas', count: 1)
            ->assertSee('Sede actualizada correctamente.');

        $this->assertDatabaseHas('sedes', [
            'id' => $sede->id,
            'nombre' => 'Sede Central',
            'tipo' => 'consumidor',
            'empleado_id' => $empleado->id,
            'direccion' => 'Av. Bolívar',
            'activo' => false,
        ]);
    }

    public function test_elimina_una_sede(): void
    {
        $sede = $this->sede();

        Livewire::test(GestionSedes::class)
            ->call('eliminar', $sede->id)
            ->assertDispatched('sedes-actualizadas', count: 0)
            ->assertSee('Sede eliminada correctamente.');

        $this->assertDatabaseMissing('sedes', ['id' => $sede->id]);
    }

    public function test_no_elimina_una_sede_con_despachos_asociados(): void
    {
        $sede = $this->sede();

        Despacho::create([
            'numero' => 'DES-2026-11-23-001',
            'sede_id' => $sede->id,
            'user_id' => User::factory()->create()->id,
            'fecha_despacho' => '2026-11-23',
            'estado' => Despacho::ESTADO_REGISTRADO,
        ]);

        Livewire::test(GestionSedes::class)
            ->call('eliminar', $sede->id)
            ->assertSee('No se puede eliminar la sede porque tiene despachos asociados.');

        $this->assertDatabaseHas('sedes', ['id' => $sede->id]);
    }

    public function test_muestra_el_nombre_del_empleado_responsable_en_la_tabla(): void
    {
        $empleado = $this->empleado();
        $this->sede(['empleado_id' => $empleado->id]);

        Livewire::test(GestionSedes::class)
            ->assertSee('Sede Principal')
            ->assertSee('Principal')
            ->assertSee('Ana')
            ->assertSee('Pérez');
    }

    public function test_muestra_sin_asignar_cuando_no_hay_responsable(): void
    {
        $this->sede();

        Livewire::test(GestionSedes::class)
            ->assertSee('Sin asignar');
    }

    public function test_busca_por_nombre_direccion_o_responsable(): void
    {
        $ana = $this->empleado();
        $luis = $this->empleado([
            'cedula' => 'V-87654321',
            'nombres' => 'Luis',
            'apellidos' => 'Gómez',
            'cargo' => 'Almacenista',
        ]);

        $this->sede([
            'nombre' => 'Sede Principal',
            'direccion' => 'Av. Principal',
            'empleado_id' => $ana->id,
        ]);

        $this->sede([
            'nombre' => 'Sede Norte',
            'tipo' => 'consumidor',
            'direccion' => 'Calle Norte',
            'empleado_id' => $luis->id,
        ]);

        Livewire::test(GestionSedes::class)
            ->set('search', 'Norte')
            ->assertSee('Sede Norte')
            ->assertDontSee('Sede Principal')
            ->set('search', 'Gómez')
            ->assertSee('Sede Norte')
            ->assertDontSee('Sede Principal')
            ->set('search', 'Av. Principal')
            ->assertSee('Sede Principal')
            ->assertDontSee('Sede Norte');
    }
}
