<?php

namespace Tests\Feature;

use App\Livewire\GestionCategorias;
use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GestionCategoriasTest extends TestCase
{
    use RefreshDatabase;

    public function test_crea_una_categoria(): void
    {
        Livewire::test(GestionCategorias::class)
            ->call('crear')
            ->assertSet('mostrarModal', true)
            ->set('nombre', 'Abarrotes')
            ->set('descripcion', 'Productos de consumo básico')
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSee('Categoría registrada correctamente.');

        $this->assertDatabaseHas('categorias', [
            'nombre' => 'Abarrotes',
            'descripcion' => 'Productos de consumo básico',
        ]);
    }

    public function test_no_permite_nombre_duplicado(): void
    {
        Categoria::create(['nombre' => 'Abarrotes']);

        Livewire::test(GestionCategorias::class)
            ->call('crear')
            ->set('nombre', 'Abarrotes')
            ->call('guardar')
            ->assertHasErrors(['nombre' => 'unique']);

        $this->assertDatabaseCount('categorias', 1);
    }

    public function test_edita_y_actualiza_una_categoria(): void
    {
        $categoria = Categoria::create(['nombre' => 'Abarrotes']);

        Livewire::test(GestionCategorias::class)
            ->call('editar', $categoria->id)
            ->assertSet('isEditing', true)
            ->assertSet('nombre', 'Abarrotes')
            ->set('nombre', 'Víveres')
            ->set('descripcion', 'Alimentos no perecederos')
            ->call('actualizar')
            ->assertHasNoErrors()
            ->assertSee('Categoría actualizada correctamente.');

        $this->assertDatabaseHas('categorias', [
            'id' => $categoria->id,
            'nombre' => 'Víveres',
            'descripcion' => 'Alimentos no perecederos',
        ]);
    }

    public function test_elimina_una_categoria_sin_productos(): void
    {
        $categoria = Categoria::create(['nombre' => 'Abarrotes']);

        Livewire::test(GestionCategorias::class)
            ->call('eliminar', $categoria->id)
            ->assertSee('Categoría eliminada correctamente.');

        $this->assertDatabaseMissing('categorias', ['id' => $categoria->id]);
    }

    public function test_no_elimina_una_categoria_con_productos(): void
    {
        $categoria = Categoria::create(['nombre' => 'Abarrotes']);

        Producto::create([
            'categoria_id' => $categoria->id,
            'sku' => 'HAR-001',
            'nombre' => 'Harina de maíz',
            'unidad_medida' => 'kg',
            'stock_minimo_alerta' => 5,
        ]);

        Livewire::test(GestionCategorias::class)
            ->call('eliminar', $categoria->id)
            ->assertSee('No se puede eliminar la categoría porque tiene productos asociados.');

        $this->assertDatabaseHas('categorias', ['id' => $categoria->id]);
    }

    public function test_genera_el_codigo_de_la_categoria_con_prefijo_cat(): void
    {
        $categoria = Categoria::create(['nombre' => 'Abarrotes']);

        $this->assertSame(
            'CAT-' . str_pad((string) $categoria->id, 4, '0', STR_PAD_LEFT),
            $categoria->codigo
        );
        $this->assertMatchesRegularExpression('/^CAT-\d{4}$/', $categoria->codigo);
    }

    public function test_muestra_el_codigo_en_la_tabla_de_categorias(): void
    {
        $categoria = Categoria::create(['nombre' => 'Abarrotes']);

        Livewire::test(GestionCategorias::class)
            ->assertSee('Código')
            ->assertSee($categoria->codigo);
    }

    public function test_busca_por_codigo_de_categoria(): void
    {
        $abarrotes = Categoria::create(['nombre' => 'Abarrotes']);
        $bebidas = Categoria::create(['nombre' => 'Bebidas']);

        Livewire::test(GestionCategorias::class)
            ->set('search', $abarrotes->codigo)
            ->assertSee('Abarrotes')
            ->assertDontSee('Bebidas')
            ->set('search', strtolower($bebidas->codigo))
            ->assertSee('Bebidas')
            ->assertDontSee('Abarrotes');
    }

    public function test_busca_por_nombre_o_descripcion(): void
    {
        Categoria::create(['nombre' => 'Abarrotes', 'descripcion' => 'Alimentos secos']);
        Categoria::create(['nombre' => 'Bebidas', 'descripcion' => 'Líquidos y refrescos']);

        Livewire::test(GestionCategorias::class)
            ->set('search', 'Abarrotes')
            ->assertSee('Abarrotes')
            ->assertDontSee('Bebidas')
            ->set('search', 'refrescos')
            ->assertSee('Bebidas')
            ->assertDontSee('Abarrotes');
    }
}
