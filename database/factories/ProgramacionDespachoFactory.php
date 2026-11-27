<?php

namespace Database\Factories;

use App\Models\Despacho;
use App\Models\ProgramacionDespacho;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<ProgramacionDespacho>
 */
class ProgramacionDespachoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sede = Sede::create([
            'nombre' => 'Sede '.$this->faker->unique()->words(2, true),
            'tipo' => 'consumidor',
            'direccion' => $this->faker->address(),
            'telefono' => '0293-'.$this->faker->numerify('#######'),
            'activo' => true,
        ]);

        $despacho = Despacho::create([
            'numero' => 'DES-PROG-'.$this->faker->unique()->numerify('######'),
            'sede_id' => $sede->id,
            'user_id' => null,
            'fecha_despacho' => Carbon::now()->toDateString(),
            'estado' => Despacho::ESTADO_REGISTRADO,
        ]);

        $fechaInicio = Carbon::now()->addDays(7)->toDateString();

        return [
            'despacho_origen_id' => $despacho->id,
            'sede_id' => $sede->id,
            'user_id' => User::factory(),
            'cada_dias' => 7,
            'fecha_inicio' => $fechaInicio,
            'proxima_fecha' => Carbon::parse($fechaInicio)->startOfDay(),
            'activa' => true,
        ];
    }
}
