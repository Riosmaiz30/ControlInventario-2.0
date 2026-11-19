<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Sede extends Model
{
    protected $table = 'sedes';
    
    protected $fillable = [
        'nombre', 
        'tipo', 
        'empleado_id', 
        'direccion', 
        'telefono', 
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // Relación: Una sede pertenece a un empleado (responsable)
    public function responsable()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
    }

    // Relación: Una sede realiza muchos pedidos (para futuras fases)
    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'sede_id');
    }

    protected function codigo(): Attribute
    {
        return Attribute::make(
            get: fn () => 'SED-' . str_pad($this->id, 4, '0', STR_PAD_LEFT),
        );
    }
}
