<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('home')
        : view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::view('/productos', 'productos.index')
    ->middleware('auth')
    ->name('productos.index');

Route::view('/lotes', 'lotes.index')
    ->middleware('auth')
    ->name('lotes.index');

Route::view('/proveedores', 'proveedores.index')
    ->middleware('auth')
    ->name('proveedores.index');

Route::view('/categorias', 'categorias.index')
    ->middleware('auth')
    ->name('categorias.index');

Route::view('/empleados', 'empleados.index')
    ->middleware('auth')
    ->name('empleados.index');

Route::view('/sedes', 'sedes.index')
    ->middleware('auth')
    ->name('sedes.index');

Route::view('/despachos', 'despachos.index')
    ->middleware('auth')
    ->name('despachos.index');

Route::view('/auditoria', 'auditoria.index')
    ->middleware('auth')
    ->name('auditoria.index');
