<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reemplaza los datos de compra y fabricación por la fecha de registro del lote.
     */
    public function up(): void
    {
        Schema::table('lotes', function (Blueprint $table) {
            $table->date('fecha_registro')->nullable();
        });

        Schema::table('lotes', function (Blueprint $table) {
            $table->dropColumn(['precio_compra', 'fecha_fabricacion']);
        });
    }

    /**
     * Restaura los campos anteriores de la tabla de lotes.
     */
    public function down(): void
    {
        Schema::table('lotes', function (Blueprint $table) {
            $table->decimal('precio_compra', 10, 2)->default(0);
            $table->date('fecha_fabricacion')->nullable();
        });

        Schema::table('lotes', function (Blueprint $table) {
            $table->dropColumn('fecha_registro');
        });
    }
};
