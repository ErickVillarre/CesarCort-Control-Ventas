<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\DetalleVenta;
use App\Models\Credito;
use App\Models\CreditoMovimiento;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class VentaController extends Controller
{
    public function index(Request $request)
    {
        $query = Venta::with(['cliente', 'detalles.producto'])->latest();

        if ($request->filled('fecha')) {
            $query->whereDate('created_at', $request->fecha);
        }

        return response()->json($query->get());
    }

    public function historial(Request $request)
    {
        $fecha = $request->get('fecha', now()->toDateString());

        $ventas = Venta::with(['cliente', 'detalles.producto'])
            ->whereDate('created_at', $fecha)
            ->latest()
            ->get();

        return response()->json([
            'fecha' => $fecha,
            'cantidad_ventas' => $ventas->count(),
            'total_dia' => $ventas->sum('total'),
            'ventas' => $ventas,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'cliente_id' => ['required', 'exists:clientes,id'],
            'productos' => ['required', 'array', 'min:1'],
            'productos.*.producto_id' => ['required', 'exists:productos,id'],
            'productos.*.cantidad' => ['required', 'integer', 'min:1'],
            'productos.*.precio' => ['required', 'numeric', 'min:0'],
            'metodo_pago' => ['required', Rule::in(['efectivo', 'tarjeta', 'credito'])],
            'tipo_operacion' => ['required', Rule::in(['contado', 'tarjeta', 'yape', 'transferencia', 'prestamo', 'cuenta'])],
            'monto_recibido' => ['nullable', 'numeric', 'min:0'],
        ]);

        $cliente = Cliente::find($request->cliente_id);

        if (!$cliente) {
            return response()->json(['message' => 'Cliente no encontrado'], 404);
        }

        $isCredito = $request->metodo_pago === 'credito';
        $isEfectivo = $request->metodo_pago === 'efectivo';

        try {
            DB::beginTransaction();

            $subtotal = 0;
            $itemsParaGuardar = [];

            foreach ($request->productos as $item) {
                $producto = Producto::lockForUpdate()->find($item['producto_id']);

                if (!$producto) {
                    throw new \Exception('Producto no encontrado', 404);
                }

                $cantidad = (int) $item['cantidad'];
                $precio = (float) $item['precio'];

                if ($producto->stock < $cantidad) {
                    throw new \Exception('Stock insuficiente de ' . $producto->nombre, 422);
                }

                $subtotalLinea = round($precio * $cantidad, 2);
                $subtotal += $subtotalLinea;

                $itemsParaGuardar[] = [
                    'producto' => $producto,
                    'cantidad' => $cantidad,
                    'precio' => $precio,
                    'subtotal' => $subtotalLinea,
                ];
            }

            $subtotal = round($subtotal, 2);
            $igv = round($subtotal * 0.18, 2);
            $total = round($subtotal + $igv, 2);

            $montoRecibido = $isEfectivo ? (float) ($request->monto_recibido ?? 0) : 0;
            $vuelto = 0;

            if ($isEfectivo && $montoRecibido < $total) {
                throw new \Exception('El monto recibido es insuficiente', 422);
            }

            if ($isEfectivo) {
                $vuelto = round($montoRecibido - $total, 2);
            }

            $credito = null;

            if ($isCredito) {
                $credito = Credito::where('cliente_id', $cliente->id)
                    ->where('tipo', $request->tipo_operacion)
                    ->where('estado', 'activo')
                    ->latest()
                    ->first();

                if (!$credito) {
                    throw new \Exception('El cliente no tiene un crédito/cuenta activa', 422);
                }

                if ($request->tipo_operacion === 'prestamo') {
                    if (($credito->saldo_actual + $total) > $credito->limite) {
                        throw new \Exception('Crédito excedido', 422);
                    }

                    $credito->saldo_actual = round($credito->saldo_actual + $total, 2);
                    $movimientoTipo = 'cargo';
                } else {
                    if ($credito->saldo_actual < $total) {
                        throw new \Exception('Saldo de cuenta insuficiente', 422);
                    }

                    $credito->saldo_actual = round($credito->saldo_actual - $total, 2);
                    $movimientoTipo = 'cargo';
                }

                $credito->save();

                CreditoMovimiento::create([
                    'credito_id' => $credito->id,
                    'venta_id' => null,
                    'tipo_movimiento' => $movimientoTipo,
                    'monto' => $total,
                    'saldo_resultante' => $credito->saldo_actual,
                    'observacion' => 'Movimiento generado por venta',
                    'user_id' => Auth::id(),
                ]);

                $cliente->credito = $credito->limite;
                $cliente->saldo = $credito->saldo_actual;
                $cliente->save();
            }

            $venta = Venta::create([
                'cliente_id' => $cliente->id,
                'subtotal' => $subtotal,
                'igv' => $igv,
                'total' => $total,
                'metodo_pago' => $request->metodo_pago,
                'tipo_operacion' => $request->tipo_operacion,
                'monto_recibido' => $isEfectivo ? $montoRecibido : null,
                'vuelto' => $vuelto,
            ]);

            foreach ($itemsParaGuardar as $item) {
                $producto = $item['producto'];

                $producto->stock -= $item['cantidad'];
                $producto->save();

                DetalleVenta::create([
                    'venta_id' => $venta->id,
                    'producto_id' => $producto->id,
                    'cantidad' => $item['cantidad'],
                    'precio' => $item['precio'],
                    'subtotal' => $item['subtotal'],
                ]);
            }

            if ($credito) {
                CreditoMovimiento::where('venta_id', null)
                    ->where('credito_id', $credito->id)
                    ->latest()
                    ->first()
                    ?->update(['venta_id' => $venta->id]);
            }

            DB::commit();

            return response()->json(
                $venta->load(['cliente', 'detalles.producto']),
                201
            );
        } catch (\Throwable $e) {
            DB::rollBack();

            $code = (int) $e->getCode();
            if ($code < 400) {
                $code = 500;
            }

            return response()->json([
                'message' => $e->getMessage()
            ], $code);
        }
    }

    public function show(Venta $venta)
    {
        return response()->json($venta->load(['cliente', 'detalles.producto']));
    }

    public function destroy(Venta $venta)
    {
        return response()->json(['message' => 'No implementado por ahora'], 422);
    }

    public function boleta($id)
    {
        $venta = Venta::with('cliente', 'detalles.producto')->findOrFail($id);

        $subtotal = $venta->subtotal ?: ($venta->total / 1.18);
        $igv = $venta->igv ?: ($venta->total - $subtotal);

        $pdf = Pdf::loadView('boleta', compact('venta', 'subtotal', 'igv'));

        return $pdf->download('boleta.pdf');
    }
}