<?php

use Giros\ReparacionComputadoras\Controllers\AlmacenesTallerController;
use Giros\ReparacionComputadoras\Controllers\InventarioTallerController;
use Illuminate\Support\Facades\Route;

Route::prefix('taller/inventario')->name('taller.inventario.')->group(function (): void {
    Route::post('/sucursales', [AlmacenesTallerController::class, 'storeSucursal'])->name('sucursales.store');

    Route::post('/categorias', [InventarioTallerController::class, 'storeCategoria'])->name('categorias.store');
    Route::post('/productos', [InventarioTallerController::class, 'storeProducto'])->name('productos.store');
    Route::post('/entradas', [InventarioTallerController::class, 'storeEntrada'])->name('entradas.store');
    Route::post('/salidas-directas', [InventarioTallerController::class, 'storeSalidaDirecta'])->name('salidas.store');
    Route::post('/consumos-orden', [InventarioTallerController::class, 'storeConsumoOrden'])->name('consumos.store');
    Route::post('/ajustes', [InventarioTallerController::class, 'storeAjuste'])->name('ajustes.store');

    Route::post('/transferencias', [InventarioTallerController::class, 'storeTransferencia'])->name('transferencias.store');
    Route::post('/transferencias/{transferenciaId}/despachar', [InventarioTallerController::class, 'despacharTransferencia'])->name('transferencias.despachar');
    Route::post('/transferencias/{transferenciaId}/recibir', [InventarioTallerController::class, 'recibirTransferencia'])->name('transferencias.recibir');
    Route::post('/transferencias/{transferenciaId}/cancelar', [InventarioTallerController::class, 'cancelarTransferencia'])->name('transferencias.cancelar');
});