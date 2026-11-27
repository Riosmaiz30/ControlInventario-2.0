<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->foreignId('cargo_id')
                ->nullable()
                ->after('apellidos')
                ->constrained('cargos')
                ->restrictOnDelete();
        });

        $this->migrarCargosExistentes();

        Schema::table('empleados', function (Blueprint $table) {
            $table->dropColumn('cargo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->string('cargo')->nullable()->after('apellidos');
        });

        $this->restaurarCargosEnEmpleados();

        Schema::table('empleados', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cargo_id');
        });
    }

    /**
     * Crea un cargo por cada valor distinto que exista en empleados.cargo.
     */
    private function migrarCargosExistentes(): void
    {
        $valores = DB::table('empleados')
            ->select('cargo')
            ->whereNotNull('cargo')
            ->distinct()
            ->pluck('cargo');

        foreach ($valores as $valor) {
            $nombre = trim((string) $valor);

            if ($nombre === '') {
                continue;
            }

            $cargoId = DB::table('cargos')
                ->whereRaw('LOWER(nombre) = ?', [mb_strtolower($nombre)])
                ->value('id');

            if (! $cargoId) {
                $cargoId = DB::table('cargos')->insertGetId([
                    'nombre' => $nombre,
                    'descripcion' => null,
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('empleados')
                ->where('cargo', $valor)
                ->update(['cargo_id' => $cargoId]);
        }
    }

    /**
     * Restaura el nombre del cargo en la columna antigua al revertir la migración.
     */
    private function restaurarCargosEnEmpleados(): void
    {
        $empleados = DB::table('empleados')
            ->join('cargos', 'cargos.id', '=', 'empleados.cargo_id')
            ->select('empleados.id', 'cargos.nombre')
            ->get();

        foreach ($empleados as $empleado) {
            DB::table('empleados')
                ->where('id', $empleado->id)
                ->update(['cargo' => $empleado->nombre]);
        }
    }
};
