<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lote extends Model
{
    protected $table = 'lotes';

    protected $fillable = [
        'producto_id',
        'proveedor_id',
        'numero_lote',
        'stock_inicial',
        'stock_actual',
        'fecha_registro',
        'fecha_vencimiento',
    ];

    protected $casts = [
        'stock_inicial' => 'decimal:2',
        'stock_actual' => 'decimal:2',
        'fecha_registro' => 'date',
        'fecha_vencimiento' => 'date',
    ];

    // Relación: Un lote pertenece a un producto
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    // Relación: Un lote viene de un proveedor específico
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    // Relación: Un lote puede abastecer varios detalles de despacho
    public function despachoDetalles()
    {
        return $this->hasMany(DespachoDetalle::class, 'lote_id');
    }
}
