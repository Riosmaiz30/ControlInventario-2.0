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
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // Relación: Un empleado puede tener una sede a su cargo
    public function sedeACargo()
    {
        return $this->hasOne(Sede::class, 'empleado_id');
    }
}
