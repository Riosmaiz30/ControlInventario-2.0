<?php

namespace App\Models;

use Database\Factories\CargoFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cargo extends Model
{
    /** @use HasFactory<CargoFactory> */
    use HasFactory;

    protected $table = 'cargos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // Relación: Un cargo puede estar asignado a muchos empleados.
    public function empleados(): HasMany
    {
        return $this->hasMany(Empleado::class, 'cargo_id');
    }

    // Código legible del cargo, por ejemplo: CAR-0001
    protected function codigo(): Attribute
    {
        return Attribute::make(
            get: fn () => 'CAR-'.str_pad($this->id, 4, '0', STR_PAD_LEFT),
        );
    }
}
