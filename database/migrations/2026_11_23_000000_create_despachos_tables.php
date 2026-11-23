<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea las tablas de despachos y su detalle por lote.
     */
    public function up(): void
    {
        Schema::create('despachos', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->foreignId('sede_id')->constrained('sedes')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('fecha_despacho');
            $table->text('observaciones')->nullable();
            $table->string('estado', 20)->default('registrado');
            $table->timestamps();

            $table->index(['estado', 'fecha_despacho']);
        });

        Schema::create('despacho_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('despacho_id')->constrained('despachos')->cascadeOnDelete();
            $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
            $table->foreignId('lote_id')->nullable()->constrained('lotes')->nullOnDelete();
            $table->string('producto_nombre');
            $table->string('numero_lote');
            $table->decimal('cantidad', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Elimina las tablas de despachos.
     */
    public function down(): void
    {
        Schema::dropIfExists('despacho_detalles');
        Schema::dropIfExists('despachos');
    }
};
