<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to] = $this->range($request);

        return response()->json([
            'rango' => compact('from', 'to'),
            'ventas' => $this->ventas($from, $to),
            'caja' => DB::table('venta_pagos')->selectRaw('metodo, SUM(monto) as total, COUNT(*) as operaciones')->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])->groupBy('metodo')->get(),
            'clientes' => DB::table('ventas')->join('clientes', 'clientes.id', '=', 'ventas.cliente_id')->selectRaw('clientes.nombre, SUM(ventas.total) as total')->whereBetween(DB::raw('DATE(ventas.created_at)'), [$from, $to])->groupBy('clientes.nombre')->orderByDesc('total')->limit(15)->get(),
            'creditos' => DB::table('creditos')->selectRaw('estado, COUNT(*) as cantidad, SUM(saldo_actual) as saldo')->groupBy('estado')->get(),
            'inventario' => DB::table('productos')->selectRaw('COUNT(*) as productos, SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) as agotados, SUM(CASE WHEN stock <= 5 THEN 1 ELSE 0 END) as criticos')->first(),
            'mantenimiento' => DB::table('mantenimiento_fallas')->selectRaw('estado, criticidad, COUNT(*) as cantidad')->groupBy('estado', 'criticidad')->get(),
            'empleados' => DB::table('empleados')->selectRaw('area, COUNT(*) as cantidad')->groupBy('area')->get(),
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        [$from, $to] = $this->range($request);

        return response()->streamDownload(function () use ($from, $to) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Fecha', 'Ventas', 'Total']);
            foreach ($this->ventas($from, $to) as $row) {
                fputcsv($handle, [$row->fecha, $row->operaciones, $row->total]);
            }
            fclose($handle);
        }, 'reporte-ventas.csv');
    }

    private function ventas(string $from, string $to)
    {
        return DB::table('ventas')
            ->selectRaw('DATE(created_at) as fecha, COUNT(*) as operaciones, SUM(subtotal) as subtotal, SUM(descuento) as descuentos, SUM(total) as total')
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();
    }

    private function range(Request $request): array
    {
        $data = $request->validate([
            'desde' => ['nullable', 'date', 'before_or_equal:today'],
            'hasta' => ['nullable', 'date', 'before_or_equal:today', 'after_or_equal:desde'],
        ]);

        return [
            $data['desde'] ?? now()->startOfMonth()->toDateString(),
            $data['hasta'] ?? now()->toDateString(),
        ];
    }
}
