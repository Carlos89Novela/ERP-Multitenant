<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Http\Controllers\Plataforma\PlataformaController;

Route::prefix('plataforma')->name('plataforma.')->middleware('operador.plataforma')->group(function () {
    Route::get('/', [PlataformaController::class, 'index'])->name('consola');
    Route::get('/monitoreo', [PlataformaController::class, 'monitoreo'])->name('monitoreo');
    Route::get('/empresas', [PlataformaController::class, 'empresas'])->name('empresas');
    Route::get('/timbres', [PlataformaController::class, 'timbres'])->name('timbres');
    Route::get('/facturacion', [PlataformaController::class, 'facturacion'])->name('facturacion');
    Route::get('/apariencia', [PlataformaController::class, 'apariencia'])->name('apariencia');

    Route::post('/template', [PlataformaController::class, 'guardarTemplatePlataforma'])->name('template.guardar');
    Route::post('/empresas', [PlataformaController::class, 'registrarEmpresa'])->name('empresas.store');
    Route::post('/empresas/{empresa}/servicio', [PlataformaController::class, 'actualizarEstadoEmpresa'])->name('empresas.servicio');
    Route::post('/empresas/{empresa}/ping', [PlataformaController::class, 'pingMonitoreo'])->name('empresas.ping');
    Route::post('/timbres/lote', [PlataformaController::class, 'comprarLoteTimbres'])->name('timbres.lote');
    Route::get('/timbres/lote/{lote}/comprobante/{tipo}', [PlataformaController::class, 'descargarComprobanteLote'])->name('timbres.comprobante');
    Route::post('/timbres/asignar', [PlataformaController::class, 'asignarTimbresEmpresa'])->name('timbres.asignar');
    Route::post('/facturacion', [PlataformaController::class, 'crearFacturaCliente'])->name('facturacion.store');
    Route::post('/facturacion/{factura}/pagar', [PlataformaController::class, 'marcarFacturaPagada'])->name('facturacion.pagar');
    Route::post('/operador', [PlataformaController::class, 'registrarOperador'])->name('operador.store');
});

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Empresa\EmpresaPortalController;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->prefix('empresa')->name('empresa.')->group(function () {
    Route::get('/portal', [EmpresaPortalController::class, 'portal'])->name('portal');
});

require base_path('giros/reparacion_computadoras/routes/web.php');
