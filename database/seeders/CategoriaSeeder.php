<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    /**
     * Carga las categorías base de los productos.
     */
    public function run(): void
    {
        $categorias = [
            ['nombre' => 'Aseo personal', 'descripcion' => 'Productos para el cuidado personal.'],
            ['nombre' => 'Viveres', 'descripcion' => 'Productos de consumo.'],
            ['nombre' => 'Embutidos', 'descripcion' => 'Producto de consumo'],
            ['nombre' => 'Articulos de limpieza', 'descripcion' => 'Limpeza'],
            ['nombre' => 'Charcuteria', 'descripcion' => 'Lacteos y derivados.'],
            ['nombre' => 'Carnico', 'descripcion' => 'Carnes y derivados.'],
        ];

        foreach ($categorias as $categoria) {
            Categoria::updateOrCreate(
                ['nombre' => $categoria['nombre']],
                $categoria
            );
        }
    }
}
