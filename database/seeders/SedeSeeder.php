<?php

namespace Database\Seeders;

use App\Models\Sede;
use Illuminate\Database\Seeder;

class SedeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sedes = [
            [
                'nombre' => 'Sede Principal',
                'tipo' => 'principal',
                'telefono' => '',
                'direccion' => 'Avenida Arismendi frente al Cuartel "Gran Mariscal Antonio José De Sucre", Cumaná - Estado Sucre',
                'activo' => true,
            ],
            [
                'nombre' => 'CEIC "Estado Lara"',
                'tipo' => 'consumidor',
                'telefono' => '',
                'direccion' => 'Avenida Arismendi Frente al Cuartel "Gran Mariscal Antonio José De Sucre" al lado de la Sede Principal, Cumaná - Estado Sucre',
                'activo' => true,
            ],
            [
                'nombre' => 'CEIC "Brasil"',
                'tipo' => 'consumidor',
                'telefono' => '',
                'direccion' => 'Urbanización Brasil Sector II, Vereda 50, entre la Unidad Cívico Evangelista Y El Grupo De Rescate (UCIER), Cumaná - Estado Sucre',
                'activo' => true,
            ],
            [
                'nombre' => 'CEIC "Pedro Luis Cedeño"',
                'tipo' => 'consumidor',
                'telefono' => '',
                'direccion' => 'Ubicación Fe y Alegría, Sector Los Ranchos Calle 07 Vereda 15, Cumaná - Estado Sucre',
                'activo' => true,
            ],
            [
                'nombre' => 'COFNNA "Cruz Salmerón Acosta"',
                'tipo' => 'consumidor',
                'telefono' => '',
                'direccion' => 'Calle Principal del Barrio Bolivariano, Cruce con Barrio El Pinar, Frente al Bloque 23 de la Urbanización Villa Olímpica, Cumaná - Estado Sucre',
                'activo' => true,
            ],
            [
                'nombre' => 'COFNNA "Valentín Valiente"',
                'tipo' => 'consumidor',
                'telefono' => '',
                'direccion' => 'Calle El Parque, Poblado Turístico Caigüire Abajo Sector Las Pepitonas, Cumaná - Estado Sucre',
                'activo' => true,
            ],
            [
                'nombre' => 'COFNNA "SUCRE 01"',
                'tipo' => 'consumidor',
                'telefono' => '',
                'direccion' => 'Calle Principal de Barrio Sucre Frente a la Cancha Del Colegio "Francisco Aristiguieta Badaraco", Cumaná - Estado Sucre',
                'activo' => true,
            ],
            [
                'nombre' => 'CAI "Cumaná"',
                'tipo' => 'consumidor',
                'telefono' => '',
                'direccion' => 'Urbanización Chalet Santa Eduviges, Quinta número 46, Cumaná - Estado Sucre',
                'activo' => true,
            ],
            [
                'nombre' => 'CPP "Cumaná"',
                'tipo' => 'consumidor',
                'telefono' => '',
                'direccion' => 'Calle la Democracia, sector Río Viejo, Cumaná - Estado Sucre',
                'activo' => true,
            ],
            [
                'nombre' => 'CEIC "Carúpano"',
                'tipo' => 'consumidor',
                'telefono' => '',
                'direccion' => 'Calle La Quebrada, San Martín Sector 22 Agosto Detrás Del Estadio Jorge Coll Núñez, Colegio María Reina De López Y Del Asilo De Ancianos, Carúpano - Estado Sucre',
                'activo' => true,
            ],
            [
                'nombre' => 'CAI "Doña Menca De Leoni"',
                'tipo' => 'consumidor',
                'telefono' => '',
                'direccion' => 'Calle Principal Sector 22 De Agosto, San Martín, Al Lado Del Asilo De Anciano, Carúpano - Estado Sucre',
                'activo' => true,
            ],
            [
                'nombre' => 'CENTRO SOCIO EDUCATIVO "DR. AGUSTIN ORTIZ RODRÍGUEZ"',
                'tipo' => 'consumidor',
                'telefono' => '',
                'direccion' => 'Final de la avenida Juncal, sector Los Leones, frente al Ministerio del Ambiente, lateral al aeropuerto "generalísimo José Francisco Bermúdez", Carúpano - Estado Sucre',
                'activo' => true,
            ],
        ];

        foreach ($sedes as $sede) {
            Sede::updateOrCreate(
                ['nombre' => $sede['nombre']],
                $sede
            );
        }
    }
}
