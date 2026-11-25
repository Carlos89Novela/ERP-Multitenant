<?php

use Giros\RestauranteCafeteria\Controllers\RestauranteCafeteriaController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')
    ->prefix('giros/restaurante-cafeteria')
    ->name('giro.restaurante.')
    ->group(function () {
        Route::get('/', [RestauranteCafeteriaController::class, 'index'])->name('index');
        Route::post('/mesas', [RestauranteCafeteriaController::class, 'storeMesa'])->name('mesas.store');
        Route::post('/recetas', [RestauranteCafeteriaController::class, 'storeReceta'])->name('recetas.store');
        Route::post('/recetas/{recetaId}/toggle', [RestauranteCafeteriaController::class, 'toggleReceta'])->name('recetas.toggle');
        Route::post('/comandas', [RestauranteCafeteriaController::class, 'storeComanda'])->name('comandas.store');
        Route::post('/comandas/{comandaId}/estado', [RestauranteCafeteriaController::class, 'actualizarComanda'])->name('comandas.estado');
        Route::post('/promociones', [RestauranteCafeteriaController::class, 'storePromocion'])->name('promociones.store');
        Route::post('/promociones/{promocionId}/toggle', [RestauranteCafeteriaController::class, 'togglePromocion'])->name('promociones.toggle');
    });
