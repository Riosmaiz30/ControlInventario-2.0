<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Quita la hora y la fecha de fin: el recordatorio depende solo del periodo.
     */
    public function up(): void
    {
        Schema::table('programacion_despachos', function (Blueprint $table) {
            foreach (['hora', 'fecha_fin'] as $columna) {
                if (Schema::hasColumn('programacion_despachos', $columna)) {
                    $table->dropColumn($columna);
                }
            }
        });
    }

    /**
     * Restaura las columnas eliminadas.
     */
    public function down(): void
    {
        Schema::table('programacion_despachos', function (Blueprint $table) {
            if (! Schema::hasColumn('programacion_despachos', 'hora')) {
                $table->time('hora')->default('00:00');
            }

            if (! Schema::hasColumn('programacion_despachos', 'fecha_fin')) {
                $table->date('fecha_fin')->nullable();
            }
        });
    }
};
