<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Configuracion extends Model
{
    protected $table = 'configuraciones';

    protected $fillable = [
        'clave',
        'valor',
    ];

    /** Clave usada para autorizar la modificación de registros. */
    public const CLAVE_ADMINISTRADOR = 'clave_administrador';

    /**
     * Obtiene el valor de una configuración.
     */
    public static function obtener(string $clave): ?string
    {
        return static::query()->where('clave', $clave)->value('valor');
    }

    /**
     * Crea o actualiza una configuración.
     */
    public static function establecer(string $clave, string $valor): void
    {
        static::query()->updateOrCreate(
            ['clave' => $clave],
            ['valor' => $valor],
        );
    }

    /**
     * Indica si ya existe una clave de administrador.
     */
    public static function tieneClaveAdministrador(): bool
    {
        return filled(static::obtener(self::CLAVE_ADMINISTRADOR));
    }

    /**
     * Verifica una clave de administrador contra el hash guardado.
     */
    public static function verificarClaveAdministrador(string $clave): bool
    {
        $hash = static::obtener(self::CLAVE_ADMINISTRADOR);

        return filled($hash) && Hash::check($clave, $hash);
    }
}
