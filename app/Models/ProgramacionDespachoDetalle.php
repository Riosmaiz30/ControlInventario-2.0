<?php

namespace App\Models;

use Database\Factories\ProgramacionDespachoDetalleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @use HasFactory<ProgramacionDespachoDetalleFactory>
 */
class ProgramacionDespachoDetalle extends Model
{
    use HasFactory;

    protected $table = 'programacion_despacho_detalles';

    protected $fillable = [
        'programacion_despacho_id',
        'producto_id',
        'producto_nombre',
        'cantidad',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
    ];

    public function programacion(): BelongsTo
    {
        return $this->belongsTo(ProgramacionDespacho::class, 'programacion_despacho_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
