<?php

namespace Database\Factories;

use App\Models\ProgramacionDespacho;
use App\Models\ProgramacionDespachoDetalle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProgramacionDespachoDetalle>
 */
class ProgramacionDespachoDetalleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'programacion_despacho_id' => ProgramacionDespacho::factory(),
            'producto_id' => null,
            'producto_nombre' => $this->faker->words(3, true),
            'cantidad' => $this->faker->randomFloat(2, 1, 50),
        ];
    }
}
