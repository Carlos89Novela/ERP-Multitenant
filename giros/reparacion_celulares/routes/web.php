<?php

use App\Http\Controllers\Empresa\AutogestionTallerController;
use Giros\ReparacionCelulares\Controllers\TallerCelularesController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')
    ->prefix('giros/reparacion-celulares')
    ->name('giro.celulares.')
    ->group(function () {
        Route::get('/', [TallerCelularesController::class, 'index'])->name('index');
        Route::post('/ordenes', [TallerCelularesController::class, 'storeOrden'])->name('ordenes.store');
        Route::post('/ordenes/{ordenId}/estado', [TallerCelularesController::class, 'actualizarEstadoOrden'])->name('ordenes.estado');
        Route::post('/promociones', [TallerCelularesController::class, 'storePromocion'])->name('promociones.store');
        Route::post('/promociones/{promocionId}/toggle', [TallerCelularesController::class, 'togglePromocion'])->name('promociones.toggle');

        Route::get('/autogestion', [AutogestionTallerController::class, 'vistaCelulares'])->name('autogestion');
        Route::post('/autogestion/apariencia', fn (\Illuminate\Http\Request $r) => app(AutogestionTallerController::class)->guardarAparienciaTaller($r, 'reparacion_celulares'))->name('autogestion.apariencia');
        Route::post('/autogestion/elementos', fn (\Illuminate\Http\Request $r) => app(AutogestionTallerController::class)->storeElementoTaller($r, 'reparacion_celulares'))->name('autogestion.elementos.store');
        Route::post('/autogestion/elementos/{elementoId}/toggle', fn (\Illuminate\Http\Request $r, int $elementoId) => app(AutogestionTallerController::class)->toggleElementoTaller($r, 'reparacion_celulares', $elementoId))->name('autogestion.elementos.toggle');
        Route::delete('/autogestion/elementos/{elementoId}', fn (\Illuminate\Http\Request $r, int $elementoId) => app(AutogestionTallerController::class)->destroyElementoTaller($r, 'reparacion_celulares', $elementoId))->name('autogestion.elementos.destroy');
    });
