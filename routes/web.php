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
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->prefix('empresa')->name('empresa.')->group(function () {
    Route::get('/portal', [EmpresaPortalController::class, 'portal'])->name('portal');
});

require base_path('giros/reparacion_computadoras/routes/web.php');

require base_path('giros/restaurante_cafeteria/routes/web.php');
require base_path('giros/abarrotes/routes/web.php');
require base_path('giros/ferreteria/routes/web.php');
require base_path('giros/reparacion_celulares/routes/web.php');

use App\Http\Controllers\Empresa\AutogestionPublicaController;

Route::get('/tienda/{slug}', [AutogestionPublicaController::class, 'verPublica'])->name('empresa.publica');
Route::get('/tienda/{slug}/login', [\App\Http\Controllers\Auth\AuthController::class, 'showLogin'])->name('empresa.login');

Route::middleware('auth')->prefix('empresa/autogestion')->name('empresa.autogestion.')->group(function () {
    Route::post('/apariencia', [AutogestionPublicaController::class, 'guardarApariencia'])->name('apariencia');
    Route::post('/slides', [AutogestionPublicaController::class, 'storeSlide'])->name('slides.store');
    Route::post('/slides/{slideId}/toggle', [AutogestionPublicaController::class, 'toggleSlide'])->name('slides.toggle');
    Route::delete('/slides/{slideId}', [AutogestionPublicaController::class, 'destroySlide'])->name('slides.destroy');
});

Route::middleware('auth:empresa')->group(function () {
    Route::get('/empresa/cambiar-password-obligatorio', [\App\Http\Controllers\Auth\AuthController::class, 'showCambioPasswordForzoso'])->name('empresa.password.forzoso');
    Route::post('/empresa/cambiar-password-obligatorio', [\App\Http\Controllers\Auth\AuthController::class, 'actualizarPasswordForzoso'])->name('empresa.password.forzoso.update');
});

Route::middleware('auth:web')->post('/plataforma/empresas/{empresaId}/reset-password-propietario', function (\Illuminate\Http\Request $request, int $empresaId) {
    $empresa = \App\Models\Empresa::query()->findOrFail($empresaId);
    $connGiro = \App\Models\UsuarioGiro::conexionPorGiro($empresa->giro_comercial);
    \Illuminate\Support\Facades\Config::set('database.connections.usuario_giro_runtime', config("database.connections.{$connGiro}"));
    \Illuminate\Support\Facades\DB::purge('usuario_giro_runtime');

    $propietario = \App\Models\UsuarioGiro::on('usuario_giro_runtime')
        ->where('empresa_id', $empresa->id)
        ->where('rol_empresa', 'propietario')
        ->orderBy('id')
        ->first();

    if (! $propietario) {
        return back()->withErrors(['email' => 'No existe un usuario propietario registrado en la BD del giro para esta empresa.']);
    }

    $temporal = 'CanoTemp' . random_int(1000, 9999) . '!';
    $propietario->update([
        'password' => \Illuminate\Support\Facades\Hash::make($temporal),
        'debe_cambiar_password' => true,
        'password_temporal_generada_at' => now(),
    ]);

    return back()->with('status', "Contraseña temporal generada para {$propietario->email}: {$temporal} (se exigirá cambio al ingresar).");
})->name('plataforma.empresas.reset_password_propietario');

Route::post('/login', [\App\Http\Controllers\Auth\AuthController::class, 'login'])->name('login.submit');
