<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\AuthController;

Route::post('login', [AuthController::class, 'login']);


Route::get('ventas/{id}/boleta', [VentaController::class, 'boleta']);

Route::middleware('auth:sanctum')->group(function () {
    
    Route::get('clientes/{id}/ventas', [ClienteController::class, 'ventas']);
    Route::get('clientes/{id}/ventas-fecha', [ClienteController::class, 'ventasPorFecha']);
    Route::get('clientes/{id}/ventas-mes', [ClienteController::class, 'ventasPorMes']);
    Route::get('clientes/buscar', [ClienteController::class, 'buscar']);
    Route::get('productos/buscar', [ProductoController::class, 'buscar']);
    Route::get('productos/autocomplete', [ProductoController::class, 'autocomplete']);
    Route::get('clientes/{id}/historial', [ClienteController::class, 'historial']);
    

    Route::apiResource('productos', ProductoController::class);
    Route::apiResource('clientes', ClienteController::class);
    Route::apiResource('ventas', VentaController::class);
    Route::apiResource('productos', ProductoController::class);
});
