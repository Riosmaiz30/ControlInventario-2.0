<?php

namespace App\AdminLte\Filters;

use App\Models\Sede;
use JeroenNoten\LaravelAdminLte\Menu\Filters\FilterInterface;

class SedeBadgeFilter implements FilterInterface
{
    /**
     * Reemplaza el badge estático del menú de Sedes por el total real
     * de registros existentes en la tabla `sedes`.
     *
     * @param  array  $item
     * @return array
     */
    public function transform($item)
    {
        if (($item['dynamic_badge'] ?? null) !== 'sedes') {
            return $item;
        }

        $item['label'] = Sede::query()->count();

        return $item;
    }
}
