<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->foreignId('proveedor_id')->constrained('proveedores')->onDelete('restrict');
            $table->string('numero_lote');
            $table->decimal('stock_inicial', 10, 2);
            $table->decimal('stock_actual', 10, 2);
            $table->decimal('precio_compra', 10, 2);
            $table->date('fecha_fabricacion')->nullable();
            $table->date('fecha_vencimiento')->nullable(); // Clave para control FIFO y caducidad
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lotes');
    }
};
