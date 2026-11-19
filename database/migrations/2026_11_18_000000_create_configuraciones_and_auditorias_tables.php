<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea el almacén de configuraciones y el registro de auditoría.
     */
    public function up(): void
    {
        Schema::create('configuraciones', function (Blueprint $table) {
            $table->id();
            $table->string('clave')->unique();
            $table->text('valor');
            $table->timestamps();
        });

        Schema::create('auditorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('accion');
            $table->string('modelo');
            $table->unsignedBigInteger('modelo_id')->nullable();
            $table->string('descripcion')->nullable();
            $table->json('datos_anteriores')->nullable();
            $table->json('datos_nuevos')->nullable();
            $table->timestamps();

            $table->index(['modelo', 'modelo_id']);
        });
    }

    /**
     * Elimina las tablas de configuraciones y auditoría.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditorias');
        Schema::dropIfExists('configuraciones');
    }
};
