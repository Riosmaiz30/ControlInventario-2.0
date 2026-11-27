<?php

namespace App\Models;

use Database\Factories\ProgramacionDespachoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @use HasFactory<ProgramacionDespachoFactory>
 */
class ProgramacionDespacho extends Model
{
    use HasFactory;

    protected $table = 'programacion_despachos';

    protected $fillable = [
        'despacho_origen_id',
        'sede_id',
        'user_id',
        'cada_dias',
        'fecha_inicio',
        'proxima_fecha',
        'ultima_notificacion_at',
        'activa',
        'observaciones',
    ];

    protected $casts = [
        'cada_dias' => 'integer',
        'fecha_inicio' => 'date',
        'proxima_fecha' => 'datetime',
        'ultima_notificacion_at' => 'datetime',
        'activa' => 'boolean',
    ];

    public function despachoOrigen(): BelongsTo
    {
        return $this->belongsTo(Despacho::class, 'despacho_origen_id');
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'sede_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(ProgramacionDespachoDetalle::class, 'programacion_despacho_id');
    }

    protected function proximaFechaTexto(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->proxima_fecha?->format('d/m/Y') ?? '—',
        );
    }

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activa', true);
    }

    public function scopeVencidas(Builder $query, Carbon $fecha): Builder
    {
        return $query->where('proxima_fecha', '<=', $fecha);
    }

    /**
     * Calcula la siguiente fecha futura respetando el periodo en días.
     */
    public function siguienteFechaDesde(Carbon $referencia): Carbon
    {
        $siguiente = $this->proxima_fecha
            ? $this->proxima_fecha->copy()
            : ($this->fecha_inicio?->copy() ?? now());

        do {
            $siguiente->addDays($this->cada_dias);
        } while ($siguiente->lessThanOrEqualTo($referencia));

        return $siguiente;
    }
}
