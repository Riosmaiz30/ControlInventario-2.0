<?php

namespace App\Http\Controllers;

use App\Models\ProgramacionDespacho;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class NotificacionController extends Controller
{
    /**
     * Devuelve los datos de la campana del panel.
     */
    public function campana(): JsonResponse
    {
        Gate::authorize('ver_notificaciones');

        $usuario = auth()->user();

        if (! $usuario) {
            return response()->json([
                'label' => '',
                'dropdown' => '',
            ]);
        }

        $cantidad = $usuario->unreadNotifications()->count();
        $notificaciones = $usuario->unreadNotifications()->latest()->limit(5)->get();

        return response()->json([
            'label' => $cantidad > 0 ? (string) $cantidad : '',
            'label_color' => 'danger',
            'dropdown' => view('notificaciones.dropdown', [
                'notificaciones' => $notificaciones,
                'cantidad' => $cantidad,
            ])->render(),
        ]);
    }

    /**
     * Marca el recordatorio como leído y abre el borrador del despacho.
     */
    public function preparar(ProgramacionDespacho $programacion): RedirectResponse
    {
        Gate::authorize('preparar_despachos');

        $usuario = auth()->user();

        $usuario?->unreadNotifications()
            ->get()
            ->filter(fn ($notificacion) => (int) ($notificacion->data['programacion_id'] ?? 0) === $programacion->id)
            ->each->markAsRead();

        return redirect()->route('despachos.index', ['programacion' => $programacion->id]);
    }

    /**
     * Marca todas las notificaciones del usuario como leídas.
     */
    public function marcarTodas(): RedirectResponse
    {
        Gate::authorize('ver_notificaciones');

        auth()->user()?->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }
}
