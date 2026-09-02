<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CreditoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\MantenimientoController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\StockAlertaController;

Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('auth/change-password', [AuthController::class, 'changePassword']);

    Route::middleware('password.changed')->group(function () {
        Route::get('dashboard', DashboardController::class)->middleware('permission:dashboard.ver');

        Route::get('mantenimiento/dashboard', [MantenimientoController::class, 'dashboard'])->middleware('permission:mantenimiento.ver');
        Route::get('mantenimiento/maquinarias', [MantenimientoController::class, 'maquinas'])->middleware('permission:mantenimiento.ver');
        Route::get('mantenimiento/maquinarias/{id}', [MantenimientoController::class, 'maquina'])->middleware('permission:mantenimiento.ver');
        Route::post('mantenimiento/fallas', [MantenimientoController::class, 'registrarFalla'])->middleware('permission:mantenimiento.crear');
        Route::post('mantenimiento/registros', [MantenimientoController::class, 'registrarMantenimiento'])->middleware('permission:mantenimiento.crear');
        Route::post('mantenimiento/pedidos-repuestos', [MantenimientoController::class, 'solicitarRepuesto'])->middleware('permission:mantenimiento.crear');
        Route::post('mantenimiento/cortes-energia', [MantenimientoController::class, 'registrarCorte'])->middleware('permission:mantenimiento.crear');

        Route::get('caja/dashboard', [CajaController::class, 'dashboard'])->middleware('permission:caja.ver');
        Route::post('caja/abrir', [CajaController::class, 'abrir'])->middleware('permission:caja.crear');
        Route::post('caja/ventas/{venta}/cobrar', [CajaController::class, 'cobrar'])->middleware('permission:caja.cobrar');
        Route::post('caja/pagos/{id}/validar', [CajaController::class, 'validarPago'])->middleware('permission:caja.cobrar');
        Route::post('caja/{id}/cerrar', [CajaController::class, 'cerrar'])->middleware('permission:caja.cerrar');
        Route::post('caja/chica', [CajaController::class, 'cajaChica'])->middleware('permission:caja.crear');

        Route::get('marketing/dashboard', [MarketingController::class, 'dashboard'])->middleware('permission:marketing.ver');
        Route::get('reportes', [ReporteController::class, 'index'])->middleware('permission:reportes.ver');
        Route::get('reportes/exportar-csv', [ReporteController::class, 'exportCsv'])->middleware('permission:reportes.exportar');
        Route::get('stock-alertas', [StockAlertaController::class, 'index'])->middleware('permission:stock_alertas.ver');
        Route::post('stock-alertas', [StockAlertaController::class, 'store'])->middleware('permission:stock_alertas.crear');

        Route::get('roles', [RoleController::class, 'index'])->middleware('permission:usuarios.gestionar');
        Route::get('empleados', [EmpleadoController::class, 'index'])->middleware('permission:empleados.ver');
        Route::post('empleados', [EmpleadoController::class, 'store'])->middleware('permission:empleados.crear');
        Route::put('empleados/{empleado}', [EmpleadoController::class, 'update'])->middleware('permission:empleados.editar');
        Route::patch('empleados/{empleado}', [EmpleadoController::class, 'update'])->middleware('permission:empleados.editar');
        Route::post('empleados/{empleado}/acceso', [EmpleadoController::class, 'habilitarAcceso'])->middleware('permission:usuarios.gestionar');
        Route::post('empleados/{empleado}/desactivar-acceso', [EmpleadoController::class, 'desactivarAcceso'])->middleware('permission:usuarios.gestionar');

        Route::get('productos/buscar', [ProductoController::class, 'buscar'])->middleware('permission:productos.ver');
        Route::get('productos/autocomplete', [ProductoController::class, 'autocomplete'])->middleware('permission:productos.ver');
        Route::get('productos', [ProductoController::class, 'index'])->middleware('permission:productos.ver');
        Route::get('productos/{producto}', [ProductoController::class, 'show'])->middleware('permission:productos.ver');
        Route::post('productos', [ProductoController::class, 'store'])->middleware('permission:productos.crear');
        Route::put('productos/{producto}', [ProductoController::class, 'update'])->middleware('permission:productos.editar');
        Route::delete('productos/{producto}', [ProductoController::class, 'destroy'])->middleware('permission:productos.desactivar');

        Route::get('clientes/buscar', [ClienteController::class, 'buscar'])->middleware('permission:clientes.ver');
        Route::get('clientes/{id}/ventas', [ClienteController::class, 'ventas'])->middleware('permission:clientes.ver');
        Route::get('clientes/{id}/ventas-fecha', [ClienteController::class, 'ventasPorFecha'])->middleware('permission:clientes.ver');
        Route::get('clientes/{id}/ventas-mes', [ClienteController::class, 'ventasPorMes'])->middleware('permission:clientes.ver');
        Route::get('clientes/{id}/historial', [ClienteController::class, 'historial'])->middleware('permission:clientes.ver');
        Route::get('clientes/{id}/creditos', [ClienteController::class, 'creditos'])->middleware('permission:creditos.ver');
        Route::get('clientes', [ClienteController::class, 'index'])->middleware('permission:clientes.ver');
        Route::get('clientes/{cliente}', [ClienteController::class, 'show'])->middleware('permission:clientes.ver');
        Route::post('clientes', [ClienteController::class, 'store'])->middleware('permission:clientes.crear');
        Route::match(['put', 'post'], 'clientes/{cliente}', [ClienteController::class, 'update'])->middleware('permission:clientes.editar');
        Route::delete('clientes/{cliente}', [ClienteController::class, 'destroy'])->middleware('permission:clientes.desactivar');

        Route::get('ventas/historial', [VentaController::class, 'historial'])->middleware('permission:ventas.ver');
        Route::post('ventas/autorizaciones-precio', [VentaController::class, 'solicitarAutorizacionPrecio'])->middleware('permission:ventas.crear');
        Route::post('ventas/autorizaciones-precio/{id}/resolver', [VentaController::class, 'aprobarAutorizacionPrecio'])->middleware('permission:ventas.aprobar');
        Route::get('ventas', [VentaController::class, 'index'])->middleware('permission:ventas.ver');
        Route::get('ventas/{id}/boleta', [VentaController::class, 'boleta'])->middleware('permission:ventas.ver');
        Route::get('ventas/{venta}', [VentaController::class, 'show'])->middleware('permission:ventas.ver');
        Route::post('ventas', [VentaController::class, 'store'])->middleware('permission:ventas.crear');
        Route::delete('ventas/{venta}', [VentaController::class, 'destroy'])->middleware('permission:ventas.anular');

        Route::get('creditos', [CreditoController::class, 'index'])->middleware('permission:creditos.ver');
        Route::get('creditos/{id}', [CreditoController::class, 'show'])->middleware('permission:creditos.ver');
        Route::post('creditos', [CreditoController::class, 'store'])->middleware('permission:creditos.aprobar');
        Route::put('creditos/{id}', [CreditoController::class, 'update'])->middleware('permission:creditos.editar');
        Route::delete('creditos/{id}', [CreditoController::class, 'destroy'])->middleware('permission:creditos.desactivar');
        Route::post('creditos/{id}/abonar', [CreditoController::class, 'abonar'])->middleware('permission:caja.cobrar');
    });
});
