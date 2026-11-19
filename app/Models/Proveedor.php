<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    protected $table = 'proveedores';
    
    protected $fillable = [
        'codigo', 
        'nombre', 
        'rif', 
        'telefono', 
        'correo', 
        'direccion'
    ];
    // Relación: Un proveedor suministra muchos lotes
    public function lotes()
    {
        return $this->hasMany(Lote::class, 'proveedor_id');
    }

    //Incluye la lógica automática de generación de códigos por ejemplo: (DEL-001)
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($proveedor) {
            $prefijo = strtoupper(substr(trim($proveedor->nombre), 0, 3));

            $ultimoCodigo = self::where('codigo', 'LIKE', "{$prefijo}-%")
                ->orderByRaw('LENGTH(codigo) DESC')
                ->orderByDesc('codigo')
                ->value('codigo');

            $conteo = $ultimoCodigo
                ? ((int) substr($ultimoCodigo, strlen($prefijo) + 1)) + 1
                : 1;

            $proveedor->codigo = "{$prefijo}-" . str_pad((string) $conteo, 3, '0', STR_PAD_LEFT);
        });
    }
}
