<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotaEntregaController;
use App\Http\Controllers\NotificacionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('home')
        : view('welcome');
});

Auth::routes();

Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::view('/productos', 'productos.index')
    ->middleware(['auth', 'can:ver_productos'])
    ->name('productos.index');

Route::view('/lotes', 'lotes.index')
    ->middleware(['auth', 'can:ver_lotes'])
    ->name('lotes.index');

Route::view('/proveedores', 'proveedores.index')
    ->middleware(['auth', 'can:gestionar_catalogos'])
    ->name('proveedores.index');

Route::view('/categorias', 'categorias.index')
    ->middleware(['auth', 'can:gestionar_catalogos'])
    ->name('categorias.index');

Route::view('/marcas', 'marcas.index')
    ->middleware(['auth', 'can:gestionar_catalogos'])
    ->name('marcas.index');

Route::view('/empleados', 'empleados.index')
    ->middleware(['auth', 'can:gestionar_organizacion'])
    ->name('empleados.index');

Route::view('/cargos', 'cargos.index')
    ->middleware(['auth', 'can:gestionar_organizacion'])
    ->name('cargos.index');

Route::view('/sedes', 'sedes.index')
    ->middleware(['auth', 'can:gestionar_organizacion'])
    ->name('sedes.index');

Route::view('/usuarios', 'usuarios.index')
    ->middleware(['auth', 'can:gestionar_usuarios'])
    ->name('usuarios.index');

Route::view('/despachos', 'despachos.index')
    ->middleware(['auth', 'can:ver_despachos'])
    ->name('despachos.index');

Route::get('/despachos/{despacho}/nota-entrega', NotaEntregaController::class)
    ->middleware(['auth', 'can:ver_despachos'])
    ->name('despachos.nota-entrega');

Route::get('/notificaciones/campana', [NotificacionController::class, 'campana'])
    ->middleware(['auth', 'can:ver_notificaciones'])
    ->name('notificaciones.campana');

Route::post('/notificaciones/{programacion}/preparar', [NotificacionController::class, 'preparar'])
    ->middleware(['auth', 'can:preparar_despachos'])
    ->name('notificaciones.preparar');

Route::post('/notificaciones/leer-todas', [NotificacionController::class, 'marcarTodas'])
    ->middleware(['auth', 'can:ver_notificaciones'])
    ->name('notificaciones.marcar-todas');

Route::view('/auditoria', 'auditoria.index')
    ->middleware(['auth', 'can:ver_auditoria'])
    ->name('auditoria.index');
