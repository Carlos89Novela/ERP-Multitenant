<?php

require_once __DIR__ . '/../controllers/PublicaTallerController.php';

use Giros\ReparacionComputadoras\Controllers\PublicaTallerController;
require_once __DIR__ . '/../models/UsuarioTaller.php';
require_once __DIR__ . '/../middleware/AutenticadoTaller.php';
require_once __DIR__ . '/../controllers/LoginTallerController.php';
require_once __DIR__ . '/../controllers/DashboardTallerController.php';

use Giros\ReparacionComputadoras\Controllers\DashboardTallerController;
use Giros\ReparacionComputadoras\Controllers\LoginTallerController;
use Giros\ReparacionComputadoras\Middleware\AutenticadoTaller;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->prefix('giros/reparacion-computadoras')->group(function () {
    Route::get('/login', [LoginTallerController::class, 'showLogin'])
        ->name('taller.computadoras.login');
    Route::post('/login', [LoginTallerController::class, 'login'])
        ->name('taller.computadoras.login.submit');

    Route::middleware(AutenticadoTaller::class)->group(function () {
        Route::get('/', [DashboardTallerController::class, 'index'])
            ->name('taller.computadoras.dashboard');
        Route::post('/logout', [LoginTallerController::class, 'logout'])
            ->name('taller.computadoras.logout');
    });
});

Route::middleware('web')->prefix('giros/reparacion-computadoras')->group(function () {
    Route::get('/publica', [PublicaTallerController::class, 'show'])
        ->name('taller.computadoras.publica');
    Route::get('/publica/{slug}', [PublicaTallerController::class, 'show'])
        ->name('taller.computadoras.publica.slug');
});
