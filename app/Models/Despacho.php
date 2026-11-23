<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Despacho extends Model
{
    protected $table = 'despachos';

    public const ESTADO_REGISTRADO = 'registrado';

    public const ESTADO_ANULADO = 'anulado';

    protected $fillable = [
        'numero',
        'sede_id',
        'user_id',
        'despachado_por_id',
        'despachado_por_nombre',
        'recibido_por_id',
        'recibido_por_nombre',
        'fecha_despacho',
        'observaciones',
        'estado',
        'consumo_interno',
    ];

    protected $casts = [
        'fecha_despacho' => 'date',
        'consumo_interno' => 'boolean',
    ];

    // Relación: Un despacho se envía a una sede.
    public function sede()
    {
        return $this->belongsTo(Sede::class, 'sede_id');
    }

    // Relación: Un despacho es registrado por un usuario.
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relación: Empleado (Jefe de Almacén) que despacha la mercancía.
    public function despachadoPor()
    {
        return $this->belongsTo(Empleado::class, 'despachado_por_id');
    }

    // Relación: Empleado (Jefe de Centro) que recibe en la sede.
    public function recibidoPor()
    {
        return $this->belongsTo(Empleado::class, 'recibido_por_id');
    }

    // Relación: Un despacho tiene muchos detalles (por lote).
    public function detalles()
    {
        return $this->hasMany(DespachoDetalle::class, 'despacho_id');
    }

    // Solo despachos vigentes (no anulados).
    public function scopeRegistrados($query)
    {
        return $query->where('estado', self::ESTADO_REGISTRADO);
    }
}
