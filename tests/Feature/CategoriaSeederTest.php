<?php

namespace Tests\Feature;

use App\Models\Categoria;
use Database\Seeders\CategoriaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoriaSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_carga_las_categorias_base_sin_duplicarlas(): void
    {
        $this->seed(CategoriaSeeder::class);
        $this->seed(CategoriaSeeder::class);

        $this->assertDatabaseCount('categorias', 6);

        foreach ([
            'Aseo personal',
            'Viveres',
            'Embutidos',
            'Articulos de limpieza',
            'Charcuteria',
            'Carnico',
        ] as $nombre) {
            $this->assertDatabaseHas('categorias', ['nombre' => $nombre]);
        }
    }

    public function test_las_categorias_cargadas_tienen_codigo_cat(): void
    {
        $this->seed(CategoriaSeeder::class);

        Categoria::all()->each(function (Categoria $categoria) {
            $this->assertMatchesRegularExpression('/^CAT-\d{4}$/', $categoria->codigo);
        });
    }
}
