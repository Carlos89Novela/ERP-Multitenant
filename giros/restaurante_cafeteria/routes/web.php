<?php

use App\Http\Controllers\Empresa\AutogestionPublicaController;
use Giros\RestauranteCafeteria\Controllers\RestauranteCafeteriaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:empresa')
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

        Route::get('/autogestion', function (Request $request) {
            $empresa = \App\Models\Empresa::query()->findOrFail((int) $request->user()->empresa_id);
            $template = \App\Models\TemplateConfiguracion::query()->where('empresa_id', $empresa->id)->orderByDesc('id')->first()
                ?? new \App\Models\TemplateConfiguracion(\App\Models\TemplateConfiguracion::defaultsPlataforma());
            $elementos = DB::table('empresa_carrusel_slides')->where('empresa_id', $empresa->id)->orderBy('orden')->orderByDesc('id')->get();
            return view('giros.restaurante_cafeteria.autogestion', compact('empresa', 'template', 'elementos'));
        })->name('autogestion');

        Route::post('/autogestion/apariencia', fn (Request $r) => app(AutogestionPublicaController::class)->guardarApariencia($r))->name('autogestion.apariencia');
        Route::post('/autogestion/elementos', fn (Request $r) => app(AutogestionPublicaController::class)->storeSlide($r))->name('autogestion.elementos.store');
        Route::post('/autogestion/elementos/{elementoId}/toggle', fn (Request $r, int $elementoId) => app(AutogestionPublicaController::class)->toggleSlide($r, $elementoId))->name('autogestion.elementos.toggle');
        Route::delete('/autogestion/elementos/{elementoId}', fn (Request $r, int $elementoId) => app(AutogestionPublicaController::class)->destroySlide($r, $elementoId))->name('autogestion.elementos.destroy');
    });
