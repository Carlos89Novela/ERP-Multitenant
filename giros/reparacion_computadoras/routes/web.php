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
require_once __DIR__ . '/../controllers/OrdenesTallerController.php';
require_once __DIR__ . '/../controllers/CambioPasswordTemporalTallerController.php';
require_once __DIR__ . '/../controllers/CorreosSucursalTallerController.php';
$traitReparacion = __DIR__ . '/../models/UsaConexionTallerReparacion.php';
if (file_exists($traitReparacion)) {
    require_once $traitReparacion;
}
foreach (glob(__DIR__ . '/../models/*.php') ?: [] as $modeloArchivo) {
    require_once $modeloArchivo;
}
foreach (glob(__DIR__ . '/../services/*.php') ?: [] as $servicioArchivo) {
    require_once $servicioArchivo;
}
require_once __DIR__ . '/../controllers/AlmacenesTallerController.php';
require_once __DIR__ . '/../controllers/InventarioTallerController.php';

use Giros\ReparacionComputadoras\Controllers\AutogestionTallerController;
use Giros\ReparacionComputadoras\Controllers\ClienteTallerController;
use Giros\ReparacionComputadoras\Controllers\DashboardTallerController;
use Giros\ReparacionComputadoras\Controllers\AlmacenesTallerController;
use Giros\ReparacionComputadoras\Controllers\InventarioTallerController;
use Giros\ReparacionComputadoras\Controllers\CorreosSucursalTallerController;
use Giros\ReparacionComputadoras\Controllers\CambioPasswordTemporalTallerController;
use Giros\ReparacionComputadoras\Controllers\OrdenesTallerController;
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
        Route::get('/ordenes', [OrdenesTallerController::class, 'index'])
            ->name('taller.computadoras.ordenes');
        Route::get('/ordenes/{folio}', [OrdenesTallerController::class, 'ver'])
            ->name('taller.computadoras.ordenes.ver');
        Route::post('/ordenes/{folio}/actualizar', [OrdenesTallerController::class, 'actualizar'])
            ->name('taller.computadoras.ordenes.actualizar');
        Route::get('/correos-sucursales', [CorreosSucursalTallerController::class, 'index'])
            ->name('taller.computadoras.correos');
        Route::post('/correos-sucursales/{usuarioId}', [CorreosSucursalTallerController::class, 'actualizar'])
            ->name('taller.computadoras.correos.actualizar');
        Route::get('/inventario', [InventarioTallerController::class, 'index'])
            ->name('taller.computadoras.inventario');
        Route::get('/inventario/sucursales', [InventarioTallerController::class, 'index'])
            ->defaults('seccion', 'sucursales')
            ->name('taller.computadoras.inventario.sucursales');
        Route::get('/inventario/entradas', [InventarioTallerController::class, 'index'])
            ->defaults('seccion', 'entradas')
            ->name('taller.computadoras.inventario.entradas');
        Route::get('/inventario/movimientos', [InventarioTallerController::class, 'index'])
            ->defaults('seccion', 'movimientos')
            ->name('taller.computadoras.inventario.movimientos');
        Route::get('/inventario/salidas-ajustes', [InventarioTallerController::class, 'index'])
            ->defaults('seccion', 'salidas_ajustes')
            ->name('taller.computadoras.inventario.salidas_ajustes');
        Route::get('/inventario/traspasos', [InventarioTallerController::class, 'index'])
            ->defaults('seccion', 'traspasos')
            ->name('taller.computadoras.inventario.traspasos');
        Route::get('/inventario/existencias', [InventarioTallerController::class, 'index'])
            ->defaults('seccion', 'existencias')
            ->name('taller.computadoras.inventario.existencias');
        Route::post('/logout', [LoginTallerController::class, 'logout'])
            ->name('taller.computadoras.logout');
        Route::get('/cambiar-password-temporal', [CambioPasswordTemporalTallerController::class, 'show'])
            ->name('taller.computadoras.password.temporal');
        Route::post('/cambiar-password-temporal', [CambioPasswordTemporalTallerController::class, 'update'])
            ->name('taller.computadoras.password.temporal.guardar');
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

require_once __DIR__ . '/inventario.php';
