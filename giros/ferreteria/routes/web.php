<?php

use Giros\Ferreteria\Controllers\FerreteriaController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')
    ->prefix('giros/ferreteria')
    ->name('giro.ferreteria.')
    ->group(function () {
        Route::get('/', [FerreteriaController::class, 'index'])->name('index');
        Route::post('/productos', [FerreteriaController::class, 'storeProducto'])->name('productos.store');
        Route::post('/promociones', [FerreteriaController::class, 'storePromocion'])->name('promociones.store');
        Route::post('/promociones/{promocionId}/toggle', [FerreteriaController::class, 'togglePromocion'])->name('promociones.toggle');
    });
