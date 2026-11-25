<?php

use App\Http\Controllers\Empresa\AutogestionTallerController;
use Giros\ReparacionComputadoras\Controllers\TallerComputadorasController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')
    ->prefix('giros/reparacion-computadoras')
    ->name('giro.computadoras.')
    ->group(function () {
        Route::get('/', [TallerComputadorasController::class, 'index'])->name('index');
        Route::get('/ordenes', [TallerComputadorasController::class, 'ordenes'])->name('ordenes');
        Route::post('/ordenes', [TallerComputadorasController::class, 'storeOrden'])->name('ordenes.store');
        Route::post('/ordenes/{ordenId}/estado', [TallerComputadorasController::class, 'actualizarEstadoOrden'])->name('ordenes.estado');
        Route::get('/inventario', [TallerComputadorasController::class, 'inventario'])->name('inventario');
        Route::get('/transferencias', [TallerComputadorasController::class, 'transferencias'])->name('transferencias');
        Route::post('/transferencias', [TallerComputadorasController::class, 'storeTransferencia'])->name('transferencias.store');
        Route::post('/transferencias/{transferenciaId}/estado', [TallerComputadorasController::class, 'actualizarEstadoTransferencia'])->name('transferencias.estado');
        Route::get('/facturacion', [TallerComputadorasController::class, 'facturacion'])->name('facturacion');
        Route::get('/promociones', [TallerComputadorasController::class, 'promociones'])->name('promociones');
        Route::post('/promociones', [TallerComputadorasController::class, 'storePromocion'])->name('promociones.store');
        Route::post('/promociones/{promocionId}/toggle', [TallerComputadorasController::class, 'togglePromocion'])->name('promociones.toggle');

        Route::get('/autogestion', [AutogestionTallerController::class, 'vistaComputadoras'])->name('autogestion');
        Route::post('/autogestion/apariencia', fn (\Illuminate\Http\Request $r) => app(AutogestionTallerController::class)->guardarAparienciaTaller($r, 'reparacion_computadoras'))->name('autogestion.apariencia');
        Route::post('/autogestion/elementos', fn (\Illuminate\Http\Request $r) => app(AutogestionTallerController::class)->storeElementoTaller($r, 'reparacion_computadoras'))->name('autogestion.elementos.store');
        Route::post('/autogestion/elementos/{elementoId}/toggle', fn (\Illuminate\Http\Request $r, int $elementoId) => app(AutogestionTallerController::class)->toggleElementoTaller($r, 'reparacion_computadoras', $elementoId))->name('autogestion.elementos.toggle');
        Route::delete('/autogestion/elementos/{elementoId}', fn (\Illuminate\Http\Request $r, int $elementoId) => app(AutogestionTallerController::class)->destroyElementoTaller($r, 'reparacion_computadoras', $elementoId))->name('autogestion.elementos.destroy');
    });
