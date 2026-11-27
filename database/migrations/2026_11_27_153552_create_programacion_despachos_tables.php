<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea las tablas de programaciones periódicas de despacho.
     */
    public function up(): void
    {
        Schema::create('programacion_despachos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('despacho_origen_id')
                ->constrained('despachos')
                ->restrictOnDelete();
            $table->foreignId('sede_id')->constrained('sedes')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('cada_dias');
            $table->date('fecha_inicio');
            $table->timestamp('proxima_fecha');
            $table->timestamp('ultima_notificacion_at')->nullable();
            $table->boolean('activa')->default(true);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['activa', 'proxima_fecha']);
        });

        Schema::create('programacion_despacho_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programacion_despacho_id')
                ->constrained('programacion_despachos')
                ->cascadeOnDelete();
            $table->foreignId('producto_id')
                ->nullable()
                ->constrained('productos')
                ->nullOnDelete();
            $table->string('producto_nombre');
            $table->decimal('cantidad', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Elimina las tablas de programaciones.
     */
    public function down(): void
    {
        Schema::dropIfExists('programacion_despacho_detalles');
        Schema::dropIfExists('programacion_despachos');
    }
};
