<?php

namespace Tests\Feature;

use App\Livewire\GestionCategorias;
use App\Livewire\GestionEmpleados;
use App\Livewire\GestionProductos;
use App\Livewire\GestionProveedores;
use App\Livewire\GestionSedes;
use App\Models\Cargo;
use App\Models\Categoria;
use App\Models\Empleado;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SweetAlertTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->administrador()->create());
    }

    private function datosBase(): void
    {
        $categoria = Categoria::create([
            'nombre' => 'Abarrotes',
            'descripcion' => 'Productos de consumo básico',
        ]);

        Empleado::create([
            'cedula' => 'V-12345678',
            'nombres' => 'Ana',
            'apellidos' => 'Pérez',
            'telefono' => '0414-1234567',
            'cargo_id' => Cargo::factory()->create(['nombre' => 'Administradora'])->id,
            'activo' => true,
        ]);

        Producto::create([
            'categoria_id' => $categoria->id,
            'sku' => 'HAR-001',
            'nombre' => 'Harina de maíz',
            'unidad_medida' => 'kg',
            'stock_minimo_alerta' => 5,
        ]);

        Proveedor::create([
            'nombre' => 'Distribuidora ABC',
            'rif' => 'J-12345678-9',
        ]);

        Sede::create([
            'nombre' => 'Sede Principal',
            'tipo' => 'principal',
            'direccion' => 'Av. Principal',
            'telefono' => '0212-1234567',
            'activo' => true,
        ]);
    }

    public function test_todos_los_modulos_con_eliminacion_usan_confirmacion_sweetalert(): void
    {
        $this->datosBase();

        $casos = [
            [GestionCategorias::class, 'esta categoría'],
            [GestionEmpleados::class, 'este empleado'],
            [GestionProductos::class, 'este producto'],
            [GestionProveedores::class, 'este proveedor'],
            [GestionSedes::class, 'esta sede'],
        ];

        foreach ($casos as [$componente, $entidad]) {
            Livewire::test($componente)
                ->assertSee('data-confirm-delete', false)
                ->assertSee('data-confirm-id="', false)
                ->assertSee('data-confirm-entity="'.$entidad.'"', false)
                ->assertDontSee('wire:confirm', false);
        }
    }

    public function test_las_alertas_de_exito_se_renderizan_para_sweetalert(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->withSession(['success' => 'Registro guardado correctamente.'])
            ->get(route('sedes.index'));

        $response->assertOk()
            ->assertSee('data-swal-flash', false)
            ->assertSee('data-type="success"', false)
            ->assertSee('data-message="Registro guardado correctamente."', false)
            ->assertDontSee('alert alert-success alert-dismissible', false);
    }

    public function test_las_alertas_de_error_se_renderizan_para_sweetalert(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->withSession(['error' => 'No se puede eliminar la sede.'])
            ->get(route('sedes.index'));

        $response->assertOk()
            ->assertSee('data-swal-flash', false)
            ->assertSee('data-type="error"', false)
            ->assertSee('data-message="No se puede eliminar la sede."', false)
            ->assertDontSee('alert alert-danger alert-dismissible', false);
    }

    public function test_una_accion_de_livewire_renderiza_la_alerta_para_sweetalert(): void
    {
        Livewire::test(GestionCategorias::class)
            ->call('crear')
            ->set('nombre', 'Abarrotes')
            ->set('descripcion', 'Productos de consumo básico')
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSee('data-swal-flash', false)
            ->assertSee('data-type="success"', false)
            ->assertSee('data-message="Categoría registrada correctamente."', false);
    }

    public function test_el_layout_maneja_cualquier_alerta_con_sweetalert(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('home'));

        $response->assertOk()
            ->assertSee('data-swal-flash', false)
            ->assertSee('MutationObserver', false)
            ->assertSee('Swal.fire', false)
            ->assertSee("document.addEventListener('livewire:navigated'", false);
    }
}
