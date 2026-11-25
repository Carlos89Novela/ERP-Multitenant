<?php

use Giros\ReparacionComputadoras\Controllers\TallerComputadorasController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')
    ->prefix('giros/reparacion-computadoras')
    ->name('giro.computadoras.')
    ->group(function () {
        Route::get('/', [TallerComputadorasController::class, 'index'])->name('index');
        Route::post('/ordenes', [TallerComputadorasController::class, 'storeOrden'])->name('ordenes.store');
        Route::post('/ordenes/{ordenId}/estado', [TallerComputadorasController::class, 'actualizarEstadoOrden'])->name('ordenes.estado');
    });
