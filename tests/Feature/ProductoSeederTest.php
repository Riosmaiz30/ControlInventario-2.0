<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use Database\Seeders\CategoriaSeeder;
use Database\Seeders\ProductoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_carga_al_menos_cinco_productos_por_cada_categoria(): void
    {
        $this->seed(CategoriaSeeder::class);
        $this->seed(ProductoSeeder::class);

        $this->assertDatabaseCount('categorias', 6);

        Categoria::all()->each(function (Categoria $categoria) {
            $this->assertGreaterThanOrEqual(
                5,
                $categoria->productos()->count(),
                "La categoría {$categoria->nombre} tiene menos de 5 productos."
            );
        });
    }

    public function test_es_idempotente_y_no_duplica_productos(): void
    {
        $this->seed(CategoriaSeeder::class);
        $this->seed(ProductoSeeder::class);

        $total = Producto::count();

        $this->seed(ProductoSeeder::class);

        $this->assertSame($total, Producto::count());
    }

    public function test_asigna_cada_producto_a_su_categoria(): void
    {
        $this->seed(CategoriaSeeder::class);
        $this->seed(ProductoSeeder::class);

        $viveres = Categoria::where('nombre', 'Viveres')->firstOrFail();

        $this->assertDatabaseHas('productos', [
            'sku' => 'HAR-001',
            'nombre' => 'Harina de maíz',
            'categoria_id' => $viveres->id,
            'unidad_medida' => 'kg',
        ]);

        $embutidos = Categoria::where('nombre', 'Embutidos')->firstOrFail();

        $this->assertDatabaseHas('productos', [
            'sku' => 'SAL-001',
            'nombre' => 'Salchicha',
            'categoria_id' => $embutidos->id,
        ]);
    }

    public function test_genera_productos_para_categorias_nuevas(): void
    {
        $this->seed(CategoriaSeeder::class);

        $congelados = Categoria::create([
            'nombre' => 'Congelados',
            'descripcion' => 'Productos congelados',
        ]);

        $this->seed(ProductoSeeder::class);

        $this->assertSame(5, $congelados->productos()->count());
        $this->assertDatabaseHas('productos', [
            'categoria_id' => $congelados->id,
            'sku' => 'CON-001',
            'nombre' => 'Congelados - Artículo 1',
        ]);
    }
}
