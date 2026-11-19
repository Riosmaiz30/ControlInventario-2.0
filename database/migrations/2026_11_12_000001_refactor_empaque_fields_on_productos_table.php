<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Separa el empaque (bulto/paca) de la unidad de medida del producto.
     */
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['unidades_por_bulto', 'unidad_contenido']);
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->string('tipo_empaque')->nullable();
            $table->unsignedInteger('cantidad_empaque')->nullable();
        });
    }

    /**
     * Restaura los campos anteriores del contenido del bulto.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['tipo_empaque', 'cantidad_empaque']);
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->unsignedInteger('unidades_por_bulto')->nullable();
            $table->string('unidad_contenido')->nullable();
        });
    }
};
