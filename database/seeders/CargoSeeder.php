<?php

namespace Database\Seeders;

use App\Models\Cargo;
use Illuminate\Database\Seeder;

class CargoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cargos = [
            [
                'nombre' => 'Jefe de Almacén',
                'descripcion' => 'Responsable de despachar los productos desde el almacén central.',
            ],
            [
                'nombre' => 'Jefe de Centro',
                'descripcion' => 'Responsable de recibir los despachos en cada sede o centro de consumo.',
            ],
        ];

        foreach ($cargos as $cargo) {
            Cargo::query()->firstOrCreate(
                ['nombre' => $cargo['nombre']],
                ['descripcion' => $cargo['descripcion'], 'activo' => true],
            );
        }
    }
}
