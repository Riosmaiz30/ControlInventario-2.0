<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Database\Seeder;

class ProductoSeeder extends Seeder
{
    /**
     * Carga al menos cinco artículos por cada categoría existente.
     */
    public function run(): void
    {
        $catalogo = [
            'Aseo personal' => [
                ['nombre' => 'Jabón de baño', 'unidad_medida' => 'unidad', 'stock_minimo_alerta' => 10],
                ['nombre' => 'Shampoo', 'unidad_medida' => 'litro', 'stock_minimo_alerta' => 8],
                ['nombre' => 'Crema dental', 'unidad_medida' => 'unidad', 'stock_minimo_alerta' => 12],
                ['nombre' => 'Desodorante', 'unidad_medida' => 'unidad', 'stock_minimo_alerta' => 10],
                ['nombre' => 'Papel higiénico', 'unidad_medida' => 'rollo', 'stock_minimo_alerta' => 20],
                ['nombre' => 'Toallas sanitarias', 'unidad_medida' => 'paquete', 'stock_minimo_alerta' => 15],
            ],
            'Viveres' => [
                ['nombre' => 'Harina de maíz', 'unidad_medida' => 'kg', 'stock_minimo_alerta' => 20],
                ['nombre' => 'Arroz blanco', 'unidad_medida' => 'kg', 'stock_minimo_alerta' => 25],
                ['nombre' => 'Pasta larga', 'unidad_medida' => 'paquete', 'stock_minimo_alerta' => 20],
                ['nombre' => 'Aceite vegetal', 'unidad_medida' => 'litro', 'stock_minimo_alerta' => 15],
                ['nombre' => 'Azúcar refinada', 'unidad_medida' => 'kg', 'stock_minimo_alerta' => 20],
                ['nombre' => 'Café molido', 'unidad_medida' => 'paquete', 'stock_minimo_alerta' => 10],
            ],
            'Embutidos' => [
                ['nombre' => 'Jamón de pierna', 'unidad_medida' => 'kg', 'stock_minimo_alerta' => 5],
                ['nombre' => 'Mortadela', 'unidad_medida' => 'kg', 'stock_minimo_alerta' => 8],
                ['nombre' => 'Salchicha', 'unidad_medida' => 'paquete', 'stock_minimo_alerta' => 10],
                ['nombre' => 'Chorizo', 'unidad_medida' => 'kg', 'stock_minimo_alerta' => 6],
                ['nombre' => 'Salami', 'unidad_medida' => 'unidad', 'stock_minimo_alerta' => 8],
            ],
            'Articulos de limpieza' => [
                ['nombre' => 'Detergente en polvo', 'unidad_medida' => 'kg', 'stock_minimo_alerta' => 10],
                ['nombre' => 'Cloro', 'unidad_medida' => 'litro', 'stock_minimo_alerta' => 12],
                ['nombre' => 'Jabón de lavar', 'unidad_medida' => 'unidad', 'stock_minimo_alerta' => 15],
                ['nombre' => 'Limpiador de pisos', 'unidad_medida' => 'litro', 'stock_minimo_alerta' => 10],
                ['nombre' => 'Esponja de cocina', 'unidad_medida' => 'paquete', 'stock_minimo_alerta' => 10],
                ['nombre' => 'Bolsas de basura', 'unidad_medida' => 'paquete', 'stock_minimo_alerta' => 8],
            ],
            'Charcuteria' => [
                ['nombre' => 'Queso blanco', 'unidad_medida' => 'kg', 'stock_minimo_alerta' => 5],
                ['nombre' => 'Queso amarillo', 'unidad_medida' => 'kg', 'stock_minimo_alerta' => 5],
                ['nombre' => 'Mantequilla', 'unidad_medida' => 'unidad', 'stock_minimo_alerta' => 8],
                ['nombre' => 'Yogur natural', 'unidad_medida' => 'litro', 'stock_minimo_alerta' => 10],
                ['nombre' => 'Leche en polvo', 'unidad_medida' => 'paquete', 'stock_minimo_alerta' => 8],
            ],
            'Carnico' => [
                ['nombre' => 'Carne de res', 'unidad_medida' => 'kg', 'stock_minimo_alerta' => 5],
                ['nombre' => 'Pollo entero', 'unidad_medida' => 'kg', 'stock_minimo_alerta' => 8],
                ['nombre' => 'Carne de cerdo', 'unidad_medida' => 'kg', 'stock_minimo_alerta' => 5],
                ['nombre' => 'Hígado de res', 'unidad_medida' => 'kg', 'stock_minimo_alerta' => 4],
                ['nombre' => 'Pescado fresco', 'unidad_medida' => 'kg', 'stock_minimo_alerta' => 6],
            ],
        ];

        foreach (Categoria::query()->orderBy('id')->get() as $categoria) {
            $productos = $catalogo[$categoria->nombre] ?? $this->productosGenericos($categoria);

            foreach ($productos as $producto) {
                Producto::updateOrCreate(
                    [
                        'categoria_id' => $categoria->id,
                        'nombre' => $producto['nombre'],
                    ],
                    [
                        'unidad_medida' => $producto['unidad_medida'],
                        'stock_minimo_alerta' => $producto['stock_minimo_alerta'],
                    ]
                );
            }
        }
    }

    /**
     * Genera cinco artículos genéricos para categorías sin catálogo definido.
     *
     * @return array<int, array{nombre: string, unidad_medida: string, stock_minimo_alerta: int}>
     */
    private function productosGenericos(Categoria $categoria): array
    {
        $productos = [];

        for ($i = 1; $i <= 5; $i++) {
            $productos[] = [
                'nombre' => "{$categoria->nombre} - Artículo {$i}",
                'unidad_medida' => 'unidad',
                'stock_minimo_alerta' => 5,
            ];
        }

        return $productos;
    }
}
