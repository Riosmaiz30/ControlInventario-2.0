<?php

namespace Database\Factories;

use App\Models\Cargo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cargo>
 */
class CargoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->words(2, true),
            'descripcion' => fake()->optional()->sentence(),
            'activo' => true,
        ];
    }

    /**
     * Cargo inactivo.
     */
    public function inactivo(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }
}
