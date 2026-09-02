<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class MarketingController extends Controller
{
    public function dashboard()
    {
        $topProducts = DB::table('detalle_ventas')
            ->join('productos', 'productos.id', '=', 'detalle_ventas.producto_id')
            ->selectRaw('productos.id, productos.nombre, SUM(detalle_ventas.cantidad) as unidades, SUM(detalle_ventas.subtotal) as total')
            ->groupBy('productos.id', 'productos.nombre')
            ->orderByDesc('unidades')
            ->limit(10)
            ->get();

        $lowRotation = DB::table('productos')
            ->leftJoin('detalle_ventas', 'productos.id', '=', 'detalle_ventas.producto_id')
            ->selectRaw('productos.id, productos.nombre, productos.stock, COUNT(detalle_ventas.id) as ventas')
            ->groupBy('productos.id', 'productos.nombre', 'productos.stock')
            ->havingRaw('ventas <= 2')
            ->orderByDesc('productos.stock')
            ->limit(10)
            ->get();

        return response()->json([
            'productos_mas_vendidos' => $topProducts,
            'baja_rotacion' => $lowRotation,
            'agotados_con_demanda' => DB::table('stock_alertas')->where('tipo', 'agotado')->whereIn('estado', ['pendiente', 'en_revision'])->count(),
            'clientes_nuevos' => DB::table('clientes')->whereMonth('created_at', now()->month)->count(),
            'campanas_activas' => DB::table('marketing_campanas')->where('estado', 'activa')->get(),
            'calendario' => DB::table('marketing_publicaciones')->whereDate('programado_at', '>=', now()->toDateString())->orderBy('programado_at')->get(),
            'redes' => DB::table('marketing_redes_sociales')->orderBy('nombre')->get(),
            'sugerencias' => $lowRotation->map(fn ($p) => [
                'producto' => $p->nombre,
                'motivo' => 'Stock alto con baja rotacion registrada.',
            ])->values(),
        ]);
    }
}
