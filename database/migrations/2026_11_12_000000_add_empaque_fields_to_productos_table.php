<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega los datos del contenido cuando el producto se maneja por bulto o paca.
     */
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->unsignedInteger('unidades_por_bulto')->nullable();
            $table->string('unidad_contenido')->nullable();
        });
    }

    /**
     * Elimina los datos del contenido del bulto.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['unidades_por_bulto', 'unidad_contenido']);
        });
    }
};
