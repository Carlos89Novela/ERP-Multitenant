<?php

use App\Http\Controllers\Empresa\AutogestionPublicaController;
use Giros\Abarrotes\Controllers\AbarrotesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')
    ->prefix('giros/abarrotes')
    ->name('giro.abarrotes.')
    ->group(function () {
        Route::get('/', [AbarrotesController::class, 'index'])->name('index');
        Route::post('/productos', [AbarrotesController::class, 'storeProducto'])->name('productos.store');
        Route::post('/promociones', [AbarrotesController::class, 'storePromocion'])->name('promociones.store');
        Route::post('/promociones/{promocionId}/toggle', [AbarrotesController::class, 'togglePromocion'])->name('promociones.toggle');

        Route::get('/autogestion', function (Request $request) {
            $empresa = \App\Models\Empresa::query()->findOrFail((int) $request->user()->empresa_id);
            $template = \App\Models\TemplateConfiguracion::query()->where('empresa_id', $empresa->id)->orderByDesc('id')->first()
                ?? new \App\Models\TemplateConfiguracion(\App\Models\TemplateConfiguracion::defaultsPlataforma());
            $elementos = DB::table('empresa_carrusel_slides')->where('empresa_id', $empresa->id)->orderBy('orden')->orderByDesc('id')->get();
            $etiquetaGiro = 'Abarrotes';
            $giroRutaPrefix = 'giro.abarrotes';
            $iconoDefault = '🛒';
            return view('giros.abarrotes.autogestion', compact('empresa', 'template', 'elementos', 'etiquetaGiro', 'giroRutaPrefix', 'iconoDefault'));
        })->name('autogestion');

        Route::post('/autogestion/apariencia', fn (Request $r) => app(AutogestionPublicaController::class)->guardarApariencia($r))->name('autogestion.apariencia');
        Route::post('/autogestion/elementos', fn (Request $r) => app(AutogestionPublicaController::class)->storeSlide($r))->name('autogestion.elementos.store');
        Route::post('/autogestion/elementos/{elementoId}/toggle', fn (Request $r, int $elementoId) => app(AutogestionPublicaController::class)->toggleSlide($r, $elementoId))->name('autogestion.elementos.toggle');
        Route::delete('/autogestion/elementos/{elementoId}', fn (Request $r, int $elementoId) => app(AutogestionPublicaController::class)->destroySlide($r, $elementoId))->name('autogestion.elementos.destroy');
    });
