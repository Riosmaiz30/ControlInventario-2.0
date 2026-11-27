<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Producto extends Model
{
    protected $table = 'productos';

    protected $fillable = [
        'categoria_id',
        'marca_id',
        'sku',
        'nombre',
        'unidad_medida',
        'tipo_empaque',
        'cantidad_empaque',
        'stock_minimo_alerta',
    ];

    protected $casts = [
        'cantidad_empaque' => 'integer',
    ];

    // Genera el código de producto a partir de las tres primeras letras del nombre.
    protected static function booted(): void
    {
        static::creating(function (Producto $producto) {
            if (blank($producto->sku)) {
                $producto->sku = self::generarCodigo($producto->nombre);
            }
        });
    }

    /**
     * Genera un código único con el formato AAA-001.
     */
    public static function generarCodigo(string $nombre): string
    {
        $letras = preg_replace('/[^A-Za-z]/', '', Str::ascii($nombre));
        $prefijo = strtoupper(substr($letras, 0, 3));

        if ($prefijo === '') {
            $prefijo = 'PRO';
        }

        $ultimoCodigo = self::query()
            ->where('sku', 'LIKE', "{$prefijo}-%")
            ->orderByRaw('LENGTH(sku) DESC')
            ->orderByDesc('sku')
            ->value('sku');

        $consecutivo = $ultimoCodigo
            ? ((int) substr($ultimoCodigo, strlen($prefijo) + 1)) + 1
            : 1;

        return "{$prefijo}-".str_pad((string) $consecutivo, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Indica si el producto se presenta en bulto o paca.
     */
    public function tieneEmpaque(): bool
    {
        return in_array($this->tipo_empaque, ['bulto', 'paca'], true);
    }

    /**
     * Describe el empaque, por ejemplo "Bulto de 24 kg" o "Paca de 30 unidades".
     */
    public function getDescripcionEmpaqueAttribute(): ?string
    {
        if (! $this->tieneEmpaque() || ! $this->cantidad_empaque) {
            return null;
        }

        $tipo = $this->tipo_empaque === 'bulto' ? 'Bulto' : 'Paca';
        $unidad = $this->unidad_medida;

        if ((int) $this->cantidad_empaque !== 1) {
            $unidad = match ($unidad) {
                'unidad' => 'unidades',
                'litro' => 'litros',
                'paquete' => 'paquetes',
                'caja' => 'cajas',
                'rollo' => 'rollos',
                'docena' => 'docenas',
                default => $unidad,
            };
        }

        return "{$tipo} de {$this->cantidad_empaque} {$unidad}";
    }

    // Relación: Un producto pertenece a una categoría
    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    // Relación: Un producto pertenece a una marca
    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class, 'marca_id');
    }

    // Relación: Un producto tiene muchos lotes de entrada
    public function lotes()
    {
        return $this->hasMany(Lote::class, 'producto_id');
    }
}
