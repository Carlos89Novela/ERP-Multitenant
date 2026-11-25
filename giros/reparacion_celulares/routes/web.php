<?php

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
    });
