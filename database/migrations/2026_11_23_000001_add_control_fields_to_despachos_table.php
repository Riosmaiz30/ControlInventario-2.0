<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega el personal responsable y el tipo de consumo al despacho.
     */
    public function up(): void
    {
        Schema::table('despachos', function (Blueprint $table) {
            $table->foreignId('despachado_por_id')
                ->nullable()
                ->after('user_id')
                ->constrained('empleados')
                ->nullOnDelete();

            $table->string('despachado_por_nombre')->nullable()->after('despachado_por_id');

            $table->foreignId('recibido_por_id')
                ->nullable()
                ->after('despachado_por_nombre')
                ->constrained('empleados')
                ->nullOnDelete();

            $table->string('recibido_por_nombre')->nullable()->after('recibido_por_id');

            $table->boolean('consumo_interno')->default(false)->after('estado');
        });
    }

    /**
     * Elimina los campos agregados.
     */
    public function down(): void
    {
        Schema::table('despachos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('despachado_por_id');
            $table->dropConstrainedForeignId('recibido_por_id');
            $table->dropColumn(['despachado_por_nombre', 'recibido_por_nombre', 'consumo_interno']);
        });
    }
};
