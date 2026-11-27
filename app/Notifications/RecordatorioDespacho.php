<?php

namespace App\Notifications;

use App\Models\ProgramacionDespacho;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RecordatorioDespacho extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public ProgramacionDespacho $programacion) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'programacion_id' => $this->programacion->id,
            'despacho_origen_id' => $this->programacion->despacho_origen_id,
            'numero_origen' => $this->programacion->despachoOrigen?->numero,
            'sede' => $this->programacion->sede?->nombre,
            'cada_dias' => $this->programacion->cada_dias,
            'proxima_fecha' => $this->programacion->proxima_fecha_texto,
            'productos' => $this->programacion->detalles->map(fn ($detalle) => [
                'producto' => $detalle->producto_nombre,
                'cantidad' => (float) $detalle->cantidad,
            ])->all(),
            'mensaje' => 'Es momento de preparar el próximo envío de suministros.',
        ];
    }

    /**
     * Get the notification's database type.
     */
    public function databaseType(object $notifiable): string
    {
        return 'recordatorio-despacho';
    }
}
