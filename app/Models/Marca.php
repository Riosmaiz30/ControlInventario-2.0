<?php

namespace App\Models;

use Database\Factories\MarcaFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Marca extends Model
{
    /** @use HasFactory<MarcaFactory> */
    use HasFactory;

    protected $table = 'marcas';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // Relación: Una marca puede tener muchos productos.
    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'marca_id');
    }

    // Código legible de la marca, por ejemplo: MAR-0001
    protected function codigo(): Attribute
    {
        return Attribute::make(
            get: fn () => 'MAR-'.str_pad($this->id, 4, '0', STR_PAD_LEFT),
        );
    }
}
