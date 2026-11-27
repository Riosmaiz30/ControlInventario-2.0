<?php

namespace App\Console\Commands;

use App\Models\Auditoria;
use App\Models\ProgramacionDespacho;
use App\Notifications\RecordatorioDespacho;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('despachos:enviar-recordatorios')]
#[Description('Envía los recordatorios de despachos periódicos que estén vencidos.')]
class EnviarRecordatoriosDespacho extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $ahora = now();

        $programaciones = ProgramacionDespacho::query()
            ->activas()
            ->vencidas($ahora)
            ->orderBy('proxima_fecha')
            ->pluck('id');

        $enviadas = 0;

        foreach ($programaciones as $programacionId) {
            DB::transaction(function () use ($programacionId, $ahora, &$enviadas) {
                $programacion = ProgramacionDespacho::query()
                    ->with(['sede', 'despachoOrigen', 'detalles', 'user'])
                    ->whereKey($programacionId)
                    ->lockForUpdate()
                    ->first();

                if (! $programacion || ! $programacion->activa) {
                    return;
                }

                if (! $programacion->proxima_fecha || $programacion->proxima_fecha->greaterThan($ahora)) {
                    return;
                }

                $proximaFechaAnterior = $programacion->proxima_fecha->copy();
                $siguienteFecha = $programacion->siguienteFechaDesde($ahora);

                if ($programacion->user) {
                    $programacion->user->notify(new RecordatorioDespacho($programacion));
                }

                $programacion->update([
                    'proxima_fecha' => $siguienteFecha,
                    'ultima_notificacion_at' => $ahora,
                ]);

                Auditoria::create([
                    'user_id' => $programacion->user_id,
                    'accion' => 'recordatorio_despacho_enviado',
                    'modelo' => 'programacion_despacho',
                    'modelo_id' => $programacion->id,
                    'descripcion' => "Recordatorio enviado para preparar el envío a {$programacion->sede?->nombre}",
                    'datos_anteriores' => [
                        'proxima_fecha' => $proximaFechaAnterior->toDateTimeString(),
                    ],
                    'datos_nuevos' => [
                        'proxima_fecha' => $siguienteFecha->toDateTimeString(),
                    ],
                ]);

                $enviadas++;
            });
        }

        $this->info("Recordatorios enviados: {$enviadas}.");

        return self::SUCCESS;
    }
}
