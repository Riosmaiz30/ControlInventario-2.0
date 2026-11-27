<?php

namespace Tests\Feature;

use App\Livewire\GestionMarcas;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GestionMarcasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->administrador()->create());
    }

    private function producto(Marca $marca): Producto
    {
        $categoria = Categoria::create([
            'nombre' => 'Víveres',
            'descripcion' => 'Productos de consumo básico',
        ]);

        return Producto::create([
            'categoria_id' => $categoria->id,
            'marca_id' => $marca->id,
            'nombre' => 'Harina de maíz',
            'unidad_medida' => 'kg',
            'stock_minimo_alerta' => 10,
        ]);
    }

    public function test_crea_una_marca(): void
    {
        Livewire::test(GestionMarcas::class)
            ->call('crear')
            ->assertSet('mostrarModal', true)
            ->set('nombre', 'Polar')
            ->set('descripcion', 'Alimentos y bebidas')
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSee('Marca registrada correctamente.');

        $this->assertDatabaseHas('marcas', [
            'nombre' => 'Polar',
            'descripcion' => 'Alimentos y bebidas',
            'activo' => true,
        ]);
    }

    public function test_no_permite_nombre_duplicado(): void
    {
        Marca::factory()->create(['nombre' => 'Polar']);

        Livewire::test(GestionMarcas::class)
            ->call('crear')
            ->set('nombre', 'Polar')
            ->call('guardar')
            ->assertHasErrors(['nombre' => 'unique']);

        $this->assertDatabaseCount('marcas', 1);
    }

    public function test_valida_campos_obligatorios(): void
    {
        Livewire::test(GestionMarcas::class)
            ->call('crear')
            ->call('guardar')
            ->assertHasErrors(['nombre' => 'required']);
    }

    public function test_edita_y_actualiza_una_marca(): void
    {
        $marca = Marca::factory()->create(['nombre' => 'Polar']);

        Livewire::test(GestionMarcas::class)
            ->call('editar', $marca->id)
            ->assertSet('isEditing', true)
            ->assertSet('nombre', 'Polar')
            ->set('nombre', 'Polar Venezuela')
            ->set('activo', false)
            ->call('actualizar')
            ->assertHasNoErrors()
            ->assertSee('Marca actualizada correctamente.');

        $this->assertDatabaseHas('marcas', [
            'id' => $marca->id,
            'nombre' => 'Polar Venezuela',
            'activo' => false,
        ]);
    }

    public function test_elimina_una_marca_sin_productos(): void
    {
        $marca = Marca::factory()->create();

        Livewire::test(GestionMarcas::class)
            ->call('eliminar', $marca->id)
            ->assertSee('Marca eliminada correctamente.');

        $this->assertDatabaseMissing('marcas', ['id' => $marca->id]);
    }

    public function test_no_elimina_una_marca_con_productos_asociados(): void
    {
        $marca = Marca::factory()->create();
        $this->producto($marca);

        Livewire::test(GestionMarcas::class)
            ->call('eliminar', $marca->id)
            ->assertSee('No se puede eliminar la marca porque tiene productos asociados.');

        $this->assertDatabaseHas('marcas', ['id' => $marca->id]);
    }

    public function test_busca_por_nombre_o_codigo(): void
    {
        $polar = Marca::factory()->create(['nombre' => 'Polar']);
        Marca::factory()->create(['nombre' => 'Savoy']);

        Livewire::test(GestionMarcas::class)
            ->set('search', 'Polar')
            ->assertSee('Polar')
            ->assertDontSee('Savoy')
            ->set('search', $polar->codigo)
            ->assertSee('Polar')
            ->assertDontSee('Savoy');
    }
}
