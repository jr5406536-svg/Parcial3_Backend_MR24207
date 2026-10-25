<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConstanciaController;
use App\Http\Controllers\Api\ReporteController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/verificar-codigo', [AuthController::class, 'verificarCodigo']);
    Route::get('/enrolamiento', [AuthController::class, 'enrolamiento']);
    Route::post('/cambiar-password', [AuthController::class, 'cambiarPassword']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('ability:*')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::get('/reportes/por-mes', [ReporteController::class, 'porMes']);

        Route::middleware('role:usuario')->group(function () {
            Route::get('/mis-constancias', [ConstanciaController::class, 'mias']);
            Route::post('/mis-constancias', [ConstanciaController::class, 'solicitar']);
            Route::get('/mis-constancias/{constancia}', [ConstanciaController::class, 'mia']);
            Route::get('/mis-constancias/{constancia}/qr', [ConstanciaController::class, 'qrPropio']);
        });

        Route::middleware('role:administrador')->group(function () {
            Route::get('/usuarios', [UserController::class, 'index']);
            Route::post('/usuarios', [UserController::class, 'store']);
            Route::post('/constancias', [ConstanciaController::class, 'store']);
            Route::get('/reportes/pdf', [ReporteController::class, 'pdf']);
            Route::get('/reportes/csv', [ReporteController::class, 'csv']);
            Route::get('/constancias', [ConstanciaController::class, 'index']);
            Route::get('/constancias/{constancia}', [ConstanciaController::class, 'show']);
            Route::match(['put', 'patch'], '/constancias/{constancia}', [ConstanciaController::class, 'update']);
            Route::delete('/constancias/{constancia}', [ConstanciaController::class, 'destroy']);
            Route::post('/constancias/{constancia}/enviar-correo', [ConstanciaController::class, 'enviarCorreo']);
            Route::get('/constancias/{constancia}/qr', [ConstanciaController::class, 'qr']);
            Route::get('/reportes/grafico', [ReporteController::class, 'grafico']);
        });
    });
});
