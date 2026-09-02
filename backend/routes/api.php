<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CreditoController;

Route::post('login', [AuthController::class, 'login']);

Route::get('ventas/{id}/boleta', [VentaController::class, 'boleta']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('productos/buscar', [ProductoController::class, 'buscar']);
    Route::get('productos/autocomplete', [ProductoController::class, 'autocomplete']);
    Route::get('clientes/buscar', [ClienteController::class, 'buscar']);
    Route::get('ventas/historial', [VentaController::class, 'historial']);
    Route::get('clientes/{id}/ventas', [ClienteController::class, 'ventas']);
    Route::get('clientes/{id}/ventas-fecha', [ClienteController::class, 'ventasPorFecha']);
    Route::get('clientes/{id}/ventas-mes', [ClienteController::class, 'ventasPorMes']);
    Route::get('clientes/{id}/historial', [ClienteController::class, 'historial']);
    Route::get('clientes/{id}/creditos', [ClienteController::class, 'creditos']);

    Route::apiResource('productos', ProductoController::class);
    Route::apiResource('clientes', ClienteController::class);
    Route::apiResource('ventas', VentaController::class);
    Route::apiResource('creditos', CreditoController::class);
    Route::post('creditos/{id}/abonar', [CreditoController::class, 'abonar']);
});