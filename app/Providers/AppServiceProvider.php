<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Define los permisos del sistema a partir del rol del usuario.
     */
    public function boot(): void
    {
        Gate::before(fn (User $usuario): ?bool => $usuario->esAdministrador() ? true : null);

        Gate::define('gestionar_catalogos', fn (User $usuario): bool => $usuario->role === User::ROLE_JEFE_ALMACEN);

        Gate::define('gestionar_productos', fn (User $usuario): bool => $usuario->role === User::ROLE_JEFE_ALMACEN);

        Gate::define('registrar_lotes', fn (User $usuario): bool => in_array($usuario->role, [
            User::ROLE_JEFE_ALMACEN,
            User::ROLE_AYUDANTE_ALMACEN,
        ], true));

        Gate::define('modificar_lotes', fn (User $usuario): bool => $usuario->role === User::ROLE_JEFE_ALMACEN);

        Gate::define('gestionar_organizacion', fn (User $usuario): bool => false);

        Gate::define('gestionar_usuarios', fn (User $usuario): bool => false);

        Gate::define('ver_productos', fn (User $usuario): bool => in_array($usuario->role, [
            User::ROLE_JEFE_ALMACEN,
            User::ROLE_AYUDANTE_ALMACEN,
        ], true));

        Gate::define('ver_lotes', fn (User $usuario): bool => in_array($usuario->role, [
            User::ROLE_JEFE_ALMACEN,
            User::ROLE_AYUDANTE_ALMACEN,
        ], true));

        Gate::define('ver_despachos', fn (User $usuario): bool => in_array($usuario->role, [
            User::ROLE_JEFE_ALMACEN,
            User::ROLE_AYUDANTE_ALMACEN,
        ], true));

        Gate::define('ver_notificaciones', fn (User $usuario): bool => in_array($usuario->role, [
            User::ROLE_JEFE_ALMACEN,
            User::ROLE_AYUDANTE_ALMACEN,
        ], true));

        Gate::define('preparar_despachos', fn (User $usuario): bool => in_array($usuario->role, [
            User::ROLE_JEFE_ALMACEN,
            User::ROLE_AYUDANTE_ALMACEN,
        ], true));

        Gate::define('confirmar_despachos', fn (User $usuario): bool => in_array($usuario->role, [
            User::ROLE_JEFE_ALMACEN,
            User::ROLE_AYUDANTE_ALMACEN,
        ], true));

        Gate::define('anular_despachos', fn (User $usuario): bool => $usuario->role === User::ROLE_JEFE_ALMACEN);

        Gate::define('programar_despachos', fn (User $usuario): bool => $usuario->role === User::ROLE_JEFE_ALMACEN);

        Gate::define('ver_auditoria', fn (User $usuario): bool => false);
    }
}
