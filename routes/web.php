<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


use App\Http\Controllers\Plataforma\PlataformaController;

Route::prefix('plataforma')->name('plataforma.')->middleware('operador.plataforma')->group(function () {
    Route::get('/', [PlataformaController::class, 'index'])->name('consola');
    Route::post('/template', [PlataformaController::class, 'guardarTemplatePlataforma'])->name('template.guardar');
    Route::post('/empresas', [PlataformaController::class, 'registrarEmpresa'])->name('empresas.store');
    Route::post('/empresas/{empresa}/servicio', [PlataformaController::class, 'actualizarEstadoEmpresa'])->name('empresas.servicio');
    Route::post('/empresas/{empresa}/ping', [PlataformaController::class, 'pingMonitoreo'])->name('empresas.ping');
    Route::post('/timbres/lote', [PlataformaController::class, 'comprarLoteTimbres'])->name('timbres.lote');
    Route::post('/timbres/asignar', [PlataformaController::class, 'asignarTimbresEmpresa'])->name('timbres.asignar');
    Route::post('/operador', [PlataformaController::class, 'registrarOperador'])->name('operador.store');
});
