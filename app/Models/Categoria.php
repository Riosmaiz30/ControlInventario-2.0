<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $table = 'categorias';
    
    protected $fillable = [
        'nombre', 
        'descripcion'
    ];

    // Relación: Una categoría tiene muchos productos
    public function productos()
    {
        return $this->hasMany(Producto::class, 'categoria_id');
    }

    // Código legible de la categoría, por ejemplo: CAT-0001
    protected function codigo(): Attribute
    {
        return Attribute::make(
            get: fn () => 'CAT-' . str_pad($this->id, 4, '0', STR_PAD_LEFT),
        );
    }
}
