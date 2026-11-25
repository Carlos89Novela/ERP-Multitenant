<?php

use Giros\Abarrotes\Controllers\AbarrotesController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')
    ->prefix('giros/abarrotes')
    ->name('giro.abarrotes.')
    ->group(function () {
        Route::get('/', [AbarrotesController::class, 'index'])->name('index');
        Route::post('/productos', [AbarrotesController::class, 'storeProducto'])->name('productos.store');
        Route::post('/promociones', [AbarrotesController::class, 'storePromocion'])->name('promociones.store');
        Route::post('/promociones/{promocionId}/toggle', [AbarrotesController::class, 'togglePromocion'])->name('promociones.toggle');
    });
