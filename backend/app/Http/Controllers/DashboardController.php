<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Credito;
use App\Models\Empleado;
use App\Models\Producto;
use App\Models\Venta;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $role = $request->user()->role_name;

        return match ($role) {
            'mantenimiento' => $this->maintenance(),
            'caja' => $this->cashier($request),
            'vendedor' => $this->seller($request),
            'marketing' => $this->marketing(),
            'recursos_humanos' => $this->humanResources(),
            default => $this->management(),
        };
    }

    private function seller(Request $request)
    {
        $userId = $request->user()->id;
        $monthStart = now()->startOfMonth();
        $monthlyGoal = 18000;
        $monthSales = Venta::where('vendedor_id', $userId)->where('created_at', '>=', $monthStart)->sum('total');
        $operations = Venta::where('vendedor_id', $userId)->where('created_at', '>=', $monthStart)->count();

        return response()->json([
            'scope' => 'vendedor',
            'metrics' => [
                'ventas_hoy' => Venta::where('vendedor_id', $userId)->whereDate('created_at', now()->toDateString())->sum('total'),
                'ventas_mes' => $monthSales,
                'meta_ventas' => $monthlyGoal,
                'avance_meta' => $monthlyGoal > 0 ? round(($monthSales / $monthlyGoal) * 100, 2) : 0,
                'operaciones' => $operations,
                'ticket_promedio' => $operations > 0 ? round($monthSales / $operations, 2) : 0,
                'seguimientos_pendientes' => DB::table('crm_seguimientos')->where('vendedor_id', $userId)->where('estado', 'pendiente')->count(),
                'ventas_pendientes_cobro' => Venta::where('vendedor_id', $userId)->where('estado', 'pendiente_caja')->count(),
                'productos_stock_bajo' => Producto::where('stock', '<=', 5)->count(),
                'productos_agotados' => Producto::where('stock', 0)->count(),
            ],
            'chart' => $this->salesChart(Venta::where('vendedor_id', $userId)),
            'recent_sales' => Venta::with('cliente')->where('vendedor_id', $userId)->latest()->limit(6)->get(),
            'top_products' => $this->topProducts($userId),
            'alerts' => DB::table('stock_alertas')->where('solicitado_por', $userId)->latest()->limit(6)->get(),
        ]);
    }

    private function cashier(Request $request)
    {
        $caja = DB::table('caja_aperturas')->where('responsable_id', $request->user()->id)->where('estado', 'abierta')->latest('abierta_at')->first();
        $pagos = DB::table('venta_pagos')->whereDate('created_at', now()->toDateString());

        return response()->json([
            'scope' => 'caja',
            'metrics' => [
                'caja_abierta' => $caja ? 1 : 0,
                'preventas_pendientes' => Venta::where('estado', 'pendiente_caja')->count(),
                'ventas_cobradas_hoy' => Venta::where('estado', 'cobrada')->whereDate('created_at', now()->toDateString())->count(),
                'efectivo' => (clone $pagos)->where('metodo', 'efectivo')->sum('monto'),
                'yape' => (clone $pagos)->where('metodo', 'yape')->sum('monto'),
                'transferencias' => (clone $pagos)->where('metodo', 'transferencia')->sum('monto'),
                'tarjetas' => (clone $pagos)->where('metodo', 'tarjeta')->sum('monto'),
                'pagos_pendientes_validacion' => DB::table('venta_pagos')->where('estado', 'pendiente_validacion')->count(),
            ],
            'caja_actual' => $caja,
            'recent_sales' => Venta::with(['cliente', 'vendedor'])->where('estado', 'pendiente_caja')->latest()->limit(8)->get(),
            'alerts' => DB::table('caja_movimientos')->latest()->limit(8)->get(),
        ]);
    }

    private function maintenance()
    {
        return response()->json([
            'scope' => 'mantenimiento',
            'metrics' => [
                'maquinarias_operativas' => DB::table('mantenimiento_maquinarias')->where('estado', 'operativa')->count(),
                'maquinarias_detenidas' => DB::table('mantenimiento_maquinarias')->where('estado', 'detenida')->count(),
                'maquinarias_mantenimiento' => DB::table('mantenimiento_maquinarias')->where('estado', 'mantenimiento')->count(),
                'fallas_abiertas' => DB::table('mantenimiento_fallas')->whereIn('estado', ['abierta', 'en_revision'])->count(),
                'fallas_criticas' => DB::table('mantenimiento_fallas')->where('criticidad', 'critica')->whereIn('estado', ['abierta', 'en_revision'])->count(),
                'mantenimientos_vencidos' => DB::table('mantenimiento_maquinarias')->whereDate('proximo_mantenimiento', '<', now())->count(),
                'pedidos_repuestos_pendientes' => DB::table('mantenimiento_pedidos_repuestos')->whereIn('estado', ['solicitado', 'en_revision', 'aprobado', 'pedido'])->count(),
                'repuestos_stock_bajo' => DB::table('mantenimiento_repuestos')->whereColumn('cantidad_disponible', '<=', 'stock_minimo')->count(),
                'tiempo_inactividad' => DB::table('mantenimiento_fallas')->sum('tiempo_inactividad_horas'),
                'costo_mes' => DB::table('mantenimiento_registros')->whereMonth('inicio_at', now()->month)->sum(DB::raw('costo_mano_obra + costo_repuestos')),
                'cortes_energia' => DB::table('mantenimiento_cortes_energia')->whereMonth('fecha', now()->month)->count(),
            ],
            'maquinarias' => DB::table('mantenimiento_maquinarias')->orderBy('nombre')->get(),
            'recent_sales' => [],
            'alerts' => DB::table('mantenimiento_fallas')->latest('reportada_at')->limit(6)->get(),
        ]);
    }

    private function marketing()
    {
        return response()->json([
            'scope' => 'marketing',
            'metrics' => [
                'productos_mas_vendidos' => $this->topProducts()->count(),
                'productos_baja_rotacion' => Producto::where('stock', '>', 10)->count(),
                'clientes_nuevos' => Cliente::whereMonth('created_at', now()->month)->count(),
                'campanas_activas' => DB::table('marketing_campanas')->where('estado', 'activa')->count(),
                'publicaciones_pendientes' => DB::table('marketing_publicaciones')->where('estado', 'pendiente')->count(),
            ],
            'top_products' => $this->topProducts(),
            'recent_sales' => [],
            'alerts' => DB::table('stock_alertas')->latest()->limit(6)->get(),
        ]);
    }

    private function humanResources()
    {
        return response()->json([
            'scope' => 'recursos_humanos',
            'metrics' => [
                'empleados_activos' => Empleado::where('activo', true)->count(),
                'faltas_mes' => DB::table('empleado_asistencias')->whereMonth('fecha', now()->month)->where('estado', 'falta')->count(),
                'tardanzas_mes' => DB::table('empleado_asistencias')->whereMonth('fecha', now()->month)->where('estado', 'tardanza')->count(),
                'permisos_pendientes' => DB::table('empleado_movimientos_rrhh')->where('tipo', 'permiso')->where('estado', 'pendiente')->count(),
                'adelantos_mes' => DB::table('empleado_movimientos_rrhh')->where('tipo', 'adelanto')->whereMonth('fecha', now()->month)->sum('monto'),
            ],
            'recent_sales' => [],
            'alerts' => DB::table('empleado_movimientos_rrhh')->latest()->limit(6)->get(),
        ]);
    }

    private function management()
    {
        return response()->json([
            'scope' => 'gerencia',
            'metrics' => [
                'ventas_hoy' => Venta::whereDate('created_at', now()->toDateString())->sum('total'),
                'ventas_mes' => Venta::whereMonth('created_at', now()->month)->sum('total'),
                'ventas_pendientes_caja' => Venta::where('estado', 'pendiente_caja')->count(),
                'cajas_abiertas' => DB::table('caja_aperturas')->where('estado', 'abierta')->count(),
                'creditos_vencidos' => Credito::where('estado', 'vencido')->count(),
                'productos_agotados' => Producto::where('stock', 0)->count(),
                'stock_critico' => Producto::where('stock', '<=', 5)->count(),
                'empleados_activos' => Empleado::where('activo', true)->count(),
                'fallas_criticas' => DB::table('mantenimiento_fallas')->where('criticidad', 'critica')->whereIn('estado', ['abierta', 'en_revision'])->count(),
                'campanas_activas' => DB::table('marketing_campanas')->where('estado', 'activa')->count(),
            ],
            'chart' => $this->salesChart(Venta::query()),
            'recent_sales' => Venta::with(['cliente', 'vendedor'])->latest()->limit(8)->get(),
            'top_products' => $this->topProducts(),
            'alerts' => [
                ['message' => DB::table('stock_alertas')->where('estado', 'pendiente')->count() . ' avisos de stock pendientes.'],
                ['message' => DB::table('mantenimiento_pedidos_repuestos')->where('estado', 'solicitado')->count() . ' pedidos de repuestos por revisar.'],
            ],
        ]);
    }

    private function salesChart($query)
    {
        $from = now()->subDays(6)->startOfDay();
        $salesByDay = $query
            ->selectRaw('DATE(created_at) as fecha, SUM(total) as total, COUNT(*) as cantidad')
            ->where('created_at', '>=', $from)
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get()
            ->keyBy('fecha');

        return collect(CarbonPeriod::create($from, now()))->map(function ($date) use ($salesByDay) {
            $key = $date->toDateString();
            $row = $salesByDay->get($key);

            return [
                'fecha' => $key,
                'total' => round((float) ($row->total ?? 0), 2),
                'cantidad' => (int) ($row->cantidad ?? 0),
            ];
        })->values();
    }

    private function topProducts(?int $sellerId = null)
    {
        $query = DB::table('detalle_ventas')
            ->join('ventas', 'ventas.id', '=', 'detalle_ventas.venta_id')
            ->join('productos', 'productos.id', '=', 'detalle_ventas.producto_id')
            ->selectRaw('productos.nombre, SUM(detalle_ventas.cantidad) as unidades, SUM(detalle_ventas.subtotal) as total')
            ->groupBy('productos.nombre')
            ->orderByDesc('unidades')
            ->limit(8);

        if ($sellerId) {
            $query->where('ventas.vendedor_id', $sellerId);
        }

        return $query->get();
    }
}
