<?php

namespace App\Http\Controllers;

use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CajaController extends Controller
{
    public function dashboard()
    {
        $caja = DB::table('caja_aperturas')->where('estado', 'abierta')->latest('abierta_at')->first();

        $pagosHoy = DB::table('venta_pagos')->whereDate('created_at', now()->toDateString());

        return response()->json([
            'caja_actual' => $caja,
            'preventas_pendientes' => Venta::with(['cliente', 'detalles.producto'])->where('estado', 'pendiente_caja')->latest()->get(),
            'ventas_cobradas_hoy' => Venta::whereDate('created_at', now()->toDateString())->where('estado', 'cobrada')->count(),
            'totales' => [
                'efectivo' => (clone $pagosHoy)->where('metodo', 'efectivo')->sum('monto'),
                'yape' => (clone $pagosHoy)->where('metodo', 'yape')->sum('monto'),
                'transferencia' => (clone $pagosHoy)->where('metodo', 'transferencia')->sum('monto'),
                'tarjeta' => (clone $pagosHoy)->where('metodo', 'tarjeta')->sum('monto'),
                'credito' => (clone $pagosHoy)->where('metodo', 'credito')->sum('monto'),
                'dinero_cuenta' => (clone $pagosHoy)->where('metodo', 'dinero_cuenta')->sum('monto'),
            ],
            'ultimos_movimientos' => DB::table('caja_movimientos')->latest()->limit(12)->get(),
            'pagos_pendientes_validacion' => DB::table('venta_pagos')->where('estado', 'pendiente_validacion')->latest()->get(),
        ]);
    }

    public function abrir(Request $request)
    {
        $data = $request->validate([
            'caja' => ['required', 'string', 'max:120'],
            'fondo_inicial' => ['required', 'numeric', 'min:0'],
            'observacion' => ['nullable', 'string'],
        ]);

        $open = DB::table('caja_aperturas')->where('responsable_id', $request->user()->id)->where('estado', 'abierta')->exists();

        if ($open) {
            return response()->json(['message' => 'Ya tienes una caja abierta.'], 422);
        }

        $data += [
            'codigo' => 'CAJA-' . now()->format('YmdHis'),
            'responsable_id' => $request->user()->id,
            'abierta_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $id = DB::table('caja_aperturas')->insertGetId($data);

        return response()->json(DB::table('caja_aperturas')->find($id), 201);
    }

    public function cobrar(Request $request, Venta $venta)
    {
        $data = $request->validate([
            'pagos' => ['required', 'array', 'min:1'],
            'pagos.*.metodo' => ['required', 'in:efectivo,yape,transferencia,tarjeta,credito,dinero_cuenta'],
            'pagos.*.monto' => ['required', 'numeric', 'min:0.01'],
            'pagos.*.banco' => ['nullable', 'string'],
            'pagos.*.numero_operacion' => ['nullable', 'string'],
        ]);

        $caja = DB::table('caja_aperturas')->where('responsable_id', $request->user()->id)->where('estado', 'abierta')->latest('abierta_at')->first();

        if (!$caja && !$request->user()->hasPermission('caja.cobrar_sin_apertura')) {
            return response()->json(['message' => 'Debes abrir caja antes de cobrar.'], 422);
        }

        $totalPagos = collect($data['pagos'])->sum(fn ($p) => (float) $p['monto']);

        if (round($totalPagos, 2) < round((float) $venta->total, 2)) {
            return response()->json(['message' => 'El monto cobrado no cubre el total de la venta.'], 422);
        }

        DB::transaction(function () use ($venta, $data, $caja, $request) {
            foreach ($venta->detalles as $detalle) {
                $producto = Producto::lockForUpdate()->find($detalle->producto_id);
                if ($producto && $producto->stock < $detalle->cantidad) {
                    abort(422, 'Stock insuficiente de ' . $producto->nombre);
                }
                if ($producto) {
                    $producto->decrement('stock', $detalle->cantidad);
                }
            }

            foreach ($data['pagos'] as $pago) {
                DB::table('venta_pagos')->insert([
                    'venta_id' => $venta->id,
                    'caja_apertura_id' => $caja?->id,
                    'metodo' => $pago['metodo'],
                    'monto' => $pago['monto'],
                    'banco' => $pago['banco'] ?? null,
                    'numero_operacion' => $pago['numero_operacion'] ?? null,
                    'estado' => in_array($pago['metodo'], ['yape', 'transferencia'], true) ? 'pendiente_validacion' : 'validado',
                    'validado_por' => in_array($pago['metodo'], ['yape', 'transferencia'], true) ? null : $request->user()->id,
                    'validado_at' => in_array($pago['metodo'], ['yape', 'transferencia'], true) ? null : now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($caja) {
                    DB::table('caja_movimientos')->insert([
                        'caja_apertura_id' => $caja->id,
                        'venta_id' => $venta->id,
                        'tipo' => 'ingreso',
                        'metodo' => in_array($pago['metodo'], ['credito', 'dinero_cuenta'], true) ? 'otro' : $pago['metodo'],
                        'monto' => $pago['monto'],
                        'categoria' => 'venta',
                        'descripcion' => 'Cobro de venta #' . $venta->id,
                        'registrado_por' => $request->user()->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $venta->update([
                'estado' => 'cobrada',
                'caja_apertura_id' => $caja?->id,
            ]);
        });

        return response()->json($venta->fresh(['cliente', 'detalles.producto']));
    }

    public function cerrar(Request $request, int $id)
    {
        $data = $request->validate([
            'monto_contado' => ['required', 'numeric', 'min:0'],
            'justificacion_diferencia' => ['nullable', 'string'],
        ]);

        $caja = DB::table('caja_aperturas')->find($id);
        if (!$caja || $caja->estado !== 'abierta') {
            return response()->json(['message' => 'Caja no disponible para cierre.'], 422);
        }

        $efectivo = DB::table('caja_movimientos')
            ->where('caja_apertura_id', $id)
            ->where('metodo', 'efectivo')
            ->selectRaw("SUM(CASE WHEN tipo IN ('ingreso','reposicion') THEN monto ELSE -monto END) as total")
            ->value('total') ?? 0;

        $esperado = (float) $caja->fondo_inicial + (float) $efectivo;
        $diferencia = round((float) $data['monto_contado'] - $esperado, 2);

        DB::table('caja_aperturas')->where('id', $id)->update([
            'cerrada_at' => now(),
            'monto_contado' => $data['monto_contado'],
            'diferencia' => $diferencia,
            'justificacion_diferencia' => $data['justificacion_diferencia'] ?? null,
            'estado' => abs($diferencia) > 0 ? 'pendiente_revision' : 'cerrada',
            'updated_at' => now(),
        ]);

        return response()->json(DB::table('caja_aperturas')->find($id));
    }

    public function validarPago(Request $request, int $id)
    {
        $data = $request->validate([
            'estado' => ['required', 'in:validado,rechazado'],
        ]);

        $pago = DB::table('venta_pagos')->find($id);
        if (!$pago) {
            return response()->json(['message' => 'Pago no encontrado.'], 404);
        }

        DB::table('venta_pagos')->where('id', $id)->update([
            'estado' => $data['estado'],
            'validado_por' => $request->user()->id,
            'validado_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(DB::table('venta_pagos')->find($id));
    }

    public function cajaChica(Request $request)
    {
        $data = $request->validate([
            'tipo' => ['required', 'in:fondo,reposicion,gasto'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'categoria' => ['nullable', 'string'],
            'comprobante' => ['nullable', 'string'],
            'descripcion' => ['nullable', 'string'],
        ]);

        $data['responsable_id'] = $request->user()->id;
        $data['created_at'] = now();
        $data['updated_at'] = now();

        $id = DB::table('caja_chica_movimientos')->insertGetId($data);

        return response()->json(DB::table('caja_chica_movimientos')->find($id), 201);
    }
}
