<?php

namespace App\Http\Controllers;

use App\Models\Credito;
use App\Models\CreditoMovimiento;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CajaController extends Controller
{
    public function dashboard()
    {
        $caja = DB::table('caja_aperturas')->where('responsable_id', auth()->id())->where('estado', 'abierta')->latest('abierta_at')->first();

        $pagosHoy = DB::table('venta_pagos')->whereDate('created_at', now()->toDateString())->where('estado', 'validado');

        return response()->json([
            'caja_actual' => $caja,
            'preventas_pendientes' => Venta::with(['cliente', 'vendedor', 'detalles.producto'])->where('estado', 'pendiente_caja')->latest()->get(),
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
            'codigo' => 'CAJA-' . now()->format('YmdHis') . '-' . strtoupper(\Illuminate\Support\Str::random(4)),
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

        DB::transaction(function () use ($venta, $data, $caja, $request) {
            $venta = Venta::whereKey($venta->id)->lockForUpdate()->firstOrFail();
            abort_unless($venta->estado === 'pendiente_caja', 409, 'La venta ya fue cobrada o anulada.');
            abort_if(DB::table('venta_pagos')->where('venta_id', $venta->id)->where('estado', 'pendiente_validacion')->exists(),
                409, 'Hay pagos pendientes de validación.');

            $pagado = (float) DB::table('venta_pagos')->where('venta_id', $venta->id)->where('estado', 'validado')->sum('monto');
            $faltante = round((float) $venta->total - $pagado, 2);
            $nuevoTotal = round(collect($data['pagos'])->sum(fn ($p) => (float) $p['monto']), 2);
            abort_unless($faltante > 0 && abs($nuevoTotal - $faltante) < 0.01, 422,
                'Los pagos deben sumar exactamente el saldo pendiente de la venta.');

            foreach ($data['pagos'] as $pago) {
                $pendiente = in_array($pago['metodo'], ['yape', 'transferencia'], true);
                $pagoId = DB::table('venta_pagos')->insertGetId([
                    'venta_id' => $venta->id,
                    'caja_apertura_id' => $caja?->id,
                    'metodo' => $pago['metodo'],
                    'monto' => $pago['monto'],
                    'banco' => $pago['banco'] ?? null,
                    'numero_operacion' => $pago['numero_operacion'] ?? null,
                    'estado' => $pendiente ? 'pendiente_validacion' : 'validado',
                    'validado_por' => $pendiente ? null : $request->user()->id,
                    'validado_at' => $pendiente ? null : now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                if (!$pendiente && $caja) {
                    $this->recordCashMovement($venta, $pagoId, $pago, $caja, $request->user()->id);
                }
            }

            $venta->update(['caja_apertura_id' => $caja?->id]);
            $this->finalizeIfFullyPaid($venta, $request->user()->id);
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
        if ($caja->responsable_id !== $request->user()->id && $request->user()->role_name !== 'admin') {
            return response()->json(['message' => 'Solo puede cerrar su propia caja.'], 403);
        }
        if (DB::table('venta_pagos')->where('caja_apertura_id', $id)->where('estado', 'pendiente_validacion')->exists()) {
            return response()->json(['message' => 'Valide los pagos pendientes antes del cierre.'], 422);
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

        DB::transaction(function () use ($request, $data, $id) {
            $pago = DB::table('venta_pagos')->where('id', $id)->lockForUpdate()->first();
            abort_unless($pago, 404);
            abort_unless($pago->estado === 'pendiente_validacion', 409, 'El pago ya fue revisado.');

            $venta = Venta::whereKey($pago->venta_id)->lockForUpdate()->firstOrFail();
            $caja = $pago->caja_apertura_id ? DB::table('caja_aperturas')->find($pago->caja_apertura_id) : null;
            abort_if($caja && $caja->estado !== 'abierta', 409, 'La caja ya está cerrada.');

            DB::table('venta_pagos')->where('id', $id)->update([
                'estado' => $data['estado'],
                'validado_por' => $request->user()->id,
                'validado_at' => now(),
                'updated_at' => now(),
            ]);

            if ($data['estado'] === 'validado') {
                if ($caja) {
                    $this->recordCashMovement($venta, $id, (array) $pago, $caja, $request->user()->id);
                }
                $this->finalizeIfFullyPaid($venta, $request->user()->id);
            }
        });

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

    private function finalizeIfFullyPaid(Venta $venta, int $actorId): void
    {
        if ($venta->estado === 'cobrada') {
            return;
        }

        $pagos = DB::table('venta_pagos')->where('venta_id', $venta->id)->where('estado', 'validado')->get();
        if (round((float) $pagos->sum('monto'), 2) < round((float) $venta->total, 2)) {
            return;
        }

        $quantities = $venta->detalles->groupBy('producto_id');
        foreach ($quantities as $productId => $details) {
            $product = Producto::whereKey($productId)->lockForUpdate()->firstOrFail();
            $quantity = (int) $details->sum('cantidad');
            if ($product->tipo !== 'servicio') {
                abort_unless($product->stock >= $quantity, 422, 'Stock insuficiente de '.$product->nombre);
                $product->decrement('stock', $quantity);
            }
        }

        foreach ($pagos as $pago) {
            if (!in_array($pago->metodo, ['credito', 'dinero_cuenta'], true)) {
                continue;
            }
            $type = $pago->metodo === 'credito' ? 'credito' : 'cuenta';
            $credit = Credito::where('cliente_id', $venta->cliente_id)->where('tipo', $type)
                ->where('estado', 'activo')->lockForUpdate()->first();
            abort_unless($credit, 422, 'El cliente no tiene línea activa para este pago.');

            $amount = (float) $pago->monto;
            if ($type === 'credito') {
                abort_unless((float) $credit->saldo_actual + $amount <= (float) $credit->limite, 422, 'Crédito excedido.');
                $credit->increment('saldo_actual', $amount);
                $venta->cliente->increment('saldo', $amount);
            } else {
                abort_unless((float) $credit->saldo_actual >= $amount, 422, 'Saldo de cuenta insuficiente.');
                $credit->decrement('saldo_actual', $amount);
                $venta->cliente->decrement('saldo', $amount);
            }

            CreditoMovimiento::create([
                'credito_id' => $credit->id,
                'venta_id' => $venta->id,
                'tipo_movimiento' => 'cargo',
                'monto' => $amount,
                'saldo_resultante' => $credit->fresh()->saldo_actual,
                'observacion' => 'Cobro en caja de venta #'.$venta->id,
                'user_id' => $actorId,
            ]);
        }

        $venta->update(['estado' => 'cobrada']);
    }

    private function recordCashMovement(Venta $venta, int $paymentId, array $payment, object $caja, int $actorId): void
    {
        DB::table('caja_movimientos')->insert([
            'caja_apertura_id' => $caja->id,
            'venta_id' => $venta->id,
            'tipo' => 'ingreso',
            'metodo' => in_array($payment['metodo'], ['credito', 'dinero_cuenta'], true) ? 'otro' : $payment['metodo'],
            'monto' => $payment['monto'],
            'categoria' => 'venta',
            'descripcion' => 'Pago validado #'.$paymentId.' de venta #'.$venta->id,
            'registrado_por' => $actorId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
