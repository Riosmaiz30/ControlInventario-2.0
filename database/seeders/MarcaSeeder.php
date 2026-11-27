<?php

namespace Database\Seeders;

use App\Models\Marca;
use Illuminate\Database\Seeder;

class MarcaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $marcas = [
            ['nombre' => 'Polar', 'descripcion' => 'Alimentos y bebidas'],
            ['nombre' => 'Savoy', 'descripcion' => 'Chocolates y golosinas'],
            ['nombre' => 'Nestlé', 'descripcion' => 'Alimentos y bebidas'],
            ['nombre' => 'Mary', 'descripcion' => 'Alimentos y bebidas'],
            ['nombre' => 'La India', 'descripcion' => 'Alimentos y bebidas'],
            ['nombre' => 'Primor', 'descripcion' => 'Alimentos y bebidas'],
            ['nombre' => 'Diana', 'descripcion' => 'Alimentos y bebidas'],
            ['nombre' => 'Ricachón', 'descripcion' => 'Alimentos y bebidas'],
            ['nombre' => 'Doña Emilia', 'descripcion' => 'Alimentos y bebidas'],
            ['nombre' => 'Molinos del Rey', 'descripcion' => 'Alimentos y bebidas'],
            ['nombre' => 'Vatel', 'descripcion' => 'Alimentos y bebidas'],
            ['nombre' => 'Rex', 'descripcion' => 'Alimentos y bebidas'],
            ['nombre' => 'Federal', 'descripcion' => 'Alimentos y bebidas'],
            ['nombre' => 'Yukery', 'descripcion' => 'Alimentos y bebidas'],
            ['nombre' => 'Pulp', 'descripcion' => 'Bebidas'],
            ['nombre' => 'Oncor', 'descripcion' => 'Alimentos y bebidas'],
            ['nombre' => 'Coca-Cola', 'descripcion' => 'Bebidas'],
            ['nombre' => 'Pepsi', 'descripcion' => 'Bebidas'],
            ['nombre' => 'Kraft', 'descripcion' => 'Alimentos y bebidas'],
            ['nombre' => 'Heinz', 'descripcion' => 'Alimentos y bebidas'],
            ['nombre' => 'Colgate', 'descripcion' => 'Cuidado personal'],
            ['nombre' => 'Palmolive', 'descripcion' => 'Cuidado personal'],
            ['nombre' => 'Dove', 'descripcion' => 'Cuidado personal'],
            ['nombre' => 'Sedal', 'descripcion' => 'Cuidado personal'],
            ['nombre' => 'Head & Shoulders', 'descripcion' => 'Cuidado personal'],
            ['nombre' => 'Pantene', 'descripcion' => 'Cuidado personal'],
            ['nombre' => 'Ariel', 'descripcion' => 'Limpieza del hogar'],
            ['nombre' => 'Ace', 'descripcion' => 'Limpieza del hogar'],
            ['nombre' => 'Las Llaves', 'descripcion' => 'Limpieza del hogar'],
            ['nombre' => 'Mistolín', 'descripcion' => 'Limpieza del hogar'],
            ['nombre' => 'Foca', 'descripcion' => 'Limpieza del hogar'],
            ['nombre' => 'Nevado', 'descripcion' => 'Limpieza del hogar'],
            ['nombre' => 'Cacique', 'descripcion' => 'Limpieza del hogar'],
        ];

        foreach ($marcas as $marca) {
            Marca::query()->firstOrCreate(
                ['nombre' => $marca['nombre']],
                ['descripcion' => $marca['descripcion'], 'activo' => true],
            );
        }
    }
}
