<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empleado extends Model
{
    protected $table = 'empleados';

    protected $fillable = [
        'cedula',
        'nombres',
        'apellidos',
        'telefono',
        'cargo',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // Relación: Un empleado puede tener una sede a su cargo
    public function sedeACargo()
    {
        return $this->hasOne(Sede::class, 'empleado_id');
    }

    // Relación: Despachos que este empleado realizó como Jefe de Almacén.
    public function despachosRealizados()
    {
        return $this->hasMany(Despacho::class, 'despachado_por_id');
    }

    // Relación: Despachos que este empleado recibió como Jefe de Centro.
    public function despachosRecibidos()
    {
        return $this->hasMany(Despacho::class, 'recibido_por_id');
    }
}
