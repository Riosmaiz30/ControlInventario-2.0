<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DespachoDetalle extends Model
{
    protected $table = 'despacho_detalles';

    protected $fillable = [
        'despacho_id',
        'producto_id',
        'lote_id',
        'producto_nombre',
        'numero_lote',
        'cantidad',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
    ];

    // Relación: Un detalle pertenece a un despacho.
    public function despacho()
    {
        return $this->belongsTo(Despacho::class, 'despacho_id');
    }

    // Relación: Un detalle referencia el producto despachado.
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    // Relación: Un detalle referencia el lote de origen.
    public function lote()
    {
        return $this->belongsTo(Lote::class, 'lote_id');
    }
}
