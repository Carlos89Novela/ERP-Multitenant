<?php

if (is_dir(__DIR__ . '/../views')) {
    View::replaceNamespace('taller_computadoras', [__DIR__ . '/../views']);
}
require_once __DIR__ . '/../controllers/AutogestionTallerController.php';
require_once __DIR__ . '/../controllers/ClienteTallerController.php';
require_once __DIR__ . '/../controllers/PublicaTallerController.php';
require_once __DIR__ . '/../models/UsuarioTaller.php';
require_once __DIR__ . '/../middleware/AutenticadoTaller.php';
require_once __DIR__ . '/../controllers/LoginTallerController.php';
require_once __DIR__ . '/../controllers/DashboardTallerController.php';

use Giros\ReparacionComputadoras\Controllers\AutogestionTallerController;
use Giros\ReparacionComputadoras\Controllers\ClienteTallerController;
use Giros\ReparacionComputadoras\Controllers\DashboardTallerController;
use Giros\ReparacionComputadoras\Controllers\LoginTallerController;
use Giros\ReparacionComputadoras\Controllers\PublicaTallerController;
use Giros\ReparacionComputadoras\Middleware\AutenticadoTaller;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

Route::middleware('web')->prefix('giros/reparacion-computadoras')->group(function () {
    Route::get('/login', [LoginTallerController::class, 'showLogin'])
        ->name('taller.computadoras.login');
    Route::post('/login', [LoginTallerController::class, 'login'])
        ->name('taller.computadoras.login.submit');

    Route::middleware(AutenticadoTaller::class)->group(function () {
        Route::get('/', [DashboardTallerController::class, 'index'])
            ->name('taller.computadoras.dashboard');
        Route::post('/logout', [LoginTallerController::class, 'logout'])
            ->name('taller.computadoras.logout');
    });
});

Route::middleware('web')->prefix('giros/reparacion-computadoras')->group(function () {
    Route::get('/publica', [PublicaTallerController::class, 'show'])
        ->name('taller.computadoras.publica');
    Route::get('/publica/{slug}', [PublicaTallerController::class, 'show'])
        ->name('taller.computadoras.publica.slug');
});

Route::middleware('web')->prefix('giros/reparacion-computadoras')->group(function () {
    Route::get('/cliente/acceso', [ClienteTallerController::class, 'mostrarAcceso'])
        ->defaults('modo', 'login')
        ->name('taller.computadoras.cliente.acceso');
    Route::get('/cliente/login', [ClienteTallerController::class, 'mostrarAcceso'])
        ->defaults('modo', 'login')
        ->name('taller.computadoras.cliente.login');
    Route::post('/cliente/login', [ClienteTallerController::class, 'iniciarSesion'])
        ->name('taller.computadoras.cliente.login.store');
    Route::get('/cliente/registro', [ClienteTallerController::class, 'mostrarAcceso'])
        ->defaults('modo', 'registro')
        ->name('taller.computadoras.cliente.registro');
    Route::post('/cliente/registro', [ClienteTallerController::class, 'registrar'])
        ->name('taller.computadoras.cliente.registro.store');
    Route::post('/cliente/logout', [ClienteTallerController::class, 'cerrarSesion'])
        ->name('taller.computadoras.cliente.logout');
    Route::get('/cliente', [ClienteTallerController::class, 'portal'])
        ->name('taller.computadoras.cliente.portal');
    Route::post('/cliente/equipos', [ClienteTallerController::class, 'registrarEquipoYOrden'])
        ->name('taller.computadoras.cliente.equipos.store');
    Route::post('/cliente/ordenes/{ordenId}/autorizar', [ClienteTallerController::class, 'autorizarOrden'])
        ->name('taller.computadoras.cliente.ordenes.autorizar');
});

Route::middleware(['web', AutenticadoTaller::class])->prefix('giros/reparacion-computadoras')->group(function () {
    Route::get('/autogestion', [AutogestionTallerController::class, 'edit'])
        ->name('taller.computadoras.autogestion');
    Route::post('/autogestion/general', [AutogestionTallerController::class, 'updateGeneral'])
        ->name('taller.computadoras.autogestion.general');
    Route::post('/autogestion/carrusel/config', [AutogestionTallerController::class, 'actualizarCarruselConfig'])
        ->name('taller.computadoras.autogestion.carrusel.config');
    Route::post('/autogestion/imagenes', [AutogestionTallerController::class, 'agregarImagen'])
        ->name('taller.computadoras.autogestion.imagenes.store');
    Route::delete('/autogestion/imagenes/{imagenId}', [AutogestionTallerController::class, 'eliminarImagen'])
        ->name('taller.computadoras.autogestion.imagenes.destroy');
    Route::post('/autogestion/servicios', [AutogestionTallerController::class, 'agregarServicio'])
        ->name('taller.computadoras.autogestion.servicios.store');
    Route::delete('/autogestion/servicios/{indice}', [AutogestionTallerController::class, 'eliminarServicio'])
        ->name('taller.computadoras.autogestion.servicios.destroy');
    Route::post('/autogestion/promociones', [AutogestionTallerController::class, 'agregarPromocion'])
        ->name('taller.computadoras.autogestion.promociones.store');
    Route::delete('/autogestion/promociones/{indice}', [AutogestionTallerController::class, 'eliminarPromocion'])
        ->name('taller.computadoras.autogestion.promociones.destroy');
});
