<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CategoriaSeeder::class,
            MarcaSeeder::class,
            ProductoSeeder::class,
            CargoSeeder::class,
            SedeSeeder::class,
        ]);

        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'role' => User::ROLE_ADMINISTRADOR,
                'password' => Hash::make('password'),
            ]
        );

        $usuarios = [
            [
                'name' => 'Administrador',
                'email' => 'admin@inventario.local',
                'role' => User::ROLE_ADMINISTRADOR,
            ],
            [
                'name' => 'Jefe de Almacén',
                'email' => 'jefe@inventario.local',
                'role' => User::ROLE_JEFE_ALMACEN,
            ],
            [
                'name' => 'Ayudante de Almacén',
                'email' => 'ayudante@inventario.local',
                'role' => User::ROLE_AYUDANTE_ALMACEN,
            ],
        ];

        foreach ($usuarios as $usuario) {
            User::firstOrCreate(
                ['email' => $usuario['email']],
                [
                    'name' => $usuario['name'],
                    'role' => $usuario['role'],
                    'password' => Hash::make('password'),
                ]
            );
        }
    }
}
