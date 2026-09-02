<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Credito;
use App\Models\CreditoMovimiento;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class VentaController extends Controller
{
    public function index(Request $request)
    {
        $query = Venta::with(['cliente', 'vendedor', 'detalles.producto'])->latest();

        $this->applyFilters($query, $request);
        $this->scopeByRole($query, $request);

        return response()->json($query->get());
    }

    public function historial(Request $request)
    {
        $request->validate([
            'fecha' => ['nullable', 'date', 'before_or_equal:today'],
            'desde' => ['nullable', 'date', 'before_or_equal:today'],
            'hasta' => ['nullable', 'date', 'before_or_equal:today'],
            'vendedor_id' => ['nullable', 'exists:users,id'],
            'cliente_id' => ['nullable', 'exists:clientes,id'],
            'comprobante_tipo' => ['nullable', Rule::in(['boleta', 'factura', 'interno'])],
            'metodo_pago' => ['nullable', Rule::in(['efectivo', 'yape', 'transferencia', 'tarjeta', 'credito', 'dinero_cuenta', 'mixto'])],
        ]);

        $query = Venta::with(['cliente', 'vendedor', 'detalles.producto'])->latest();
        $this->applyFilters($query, $request);
        $this->scopeByRole($query, $request);

        $ventas = $query->get();

        $paymentTotals = DB::table('venta_pagos')
            ->whereIn('venta_id', $ventas->pluck('id'))
            ->selectRaw('metodo, SUM(monto) as total')
            ->groupBy('metodo')
            ->pluck('total', 'metodo');

        $productos = $ventas
            ->flatMap(fn (Venta $venta) => $venta->detalles)
            ->groupBy('producto_id')
            ->map(fn ($items) => [
                'producto' => $items->first()->producto?->nombre,
                'cantidad' => $items->sum('cantidad'),
                'subtotal' => $items->sum('subtotal'),
            ])
            ->values();

        return response()->json([
            'cantidad_ventas' => $ventas->count(),
            'productos' => $productos,
            'clientes' => $ventas->pluck('cliente.nombre')->filter()->unique()->values(),
            'vendedores' => $ventas->pluck('vendedor.name')->filter()->unique()->values(),
            'subtotal' => $ventas->sum('subtotal'),
            'descuentos' => $ventas->sum('descuento'),
            'total_dia' => $ventas->sum('total'),
            'efectivo' => (float) ($paymentTotals['efectivo'] ?? 0),
            'yape' => (float) ($paymentTotals['yape'] ?? 0),
            'transferencia' => (float) ($paymentTotals['transferencia'] ?? 0),
            'tarjeta' => (float) ($paymentTotals['tarjeta'] ?? 0),
            'credito' => (float) ($paymentTotals['credito'] ?? 0),
            'dinero_cuenta' => (float) ($paymentTotals['dinero_cuenta'] ?? 0),
            'ganancia_estimada' => $this->estimatedProfit($ventas),
            'ventas_anuladas' => $ventas->where('estado', 'anulada')->count(),
            'operaciones_pendientes' => $ventas->where('estado', 'pendiente_caja')->count(),
            'ventas' => $ventas,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'cliente_id' => ['required', 'exists:clientes,id'],
            'productos' => ['required', 'array', 'min:1'],
            'productos.*.producto_id' => ['required', 'exists:productos,id'],
            'productos.*.cantidad' => ['required', 'integer', 'min:1'],
            'productos.*.precio' => ['required', 'numeric', 'min:0'],
            'metodo_pago' => ['required', Rule::in(['efectivo', 'yape', 'transferencia', 'tarjeta', 'credito', 'dinero_cuenta', 'mixto'])],
            'tipo_operacion' => ['required', Rule::in(['contado', 'tarjeta', 'yape', 'transferencia', 'prestamo', 'cuenta', 'mixto'])],
            'comprobante_tipo' => ['required', Rule::in(['boleta', 'factura', 'interno'])],
            'monto_recibido' => ['nullable', 'numeric', 'min:0'],
            'descuento' => ['nullable', 'numeric', 'min:0'],
            'finalizar' => ['nullable', 'boolean'],
        ]);

        $cliente = Cliente::findOrFail($data['cliente_id']);
        $this->validateComprobante($data['comprobante_tipo'], $cliente);

        try {
            DB::beginTransaction();

            [$itemsParaGuardar, $subtotal] = $this->buildItems($data['productos']);
            $descuento = round((float) ($data['descuento'] ?? 0), 2);
            $subtotalConDescuento = max(0, round($subtotal - $descuento, 2));
            $igv = round($subtotalConDescuento * 0.18, 2);
            $total = round($subtotalConDescuento + $igv, 2);
            $canFinalize = $request->boolean('finalizar') && $request->user()->hasPermission('caja.cobrar');
            $isEfectivo = $data['metodo_pago'] === 'efectivo';
            $montoRecibido = $isEfectivo ? (float) ($data['monto_recibido'] ?? 0) : 0;

            if ($canFinalize && $isEfectivo && $montoRecibido < $total) {
                throw new \Exception('El monto recibido es insuficiente', 422);
            }

            $venta = Venta::create([
                'cliente_id' => $cliente->id,
                'vendedor_id' => Auth::id(),
                'subtotal' => $subtotal,
                'igv' => $igv,
                'descuento' => $descuento,
                'total' => $total,
                'estado' => $canFinalize ? 'cobrada' : 'pendiente_caja',
                'comprobante_tipo' => $data['comprobante_tipo'],
                'metodo_pago' => $data['metodo_pago'],
                'tipo_operacion' => $data['tipo_operacion'],
                'monto_recibido' => $canFinalize && $isEfectivo ? $montoRecibido : null,
                'vuelto' => $canFinalize && $isEfectivo ? round($montoRecibido - $total, 2) : 0,
            ]);

            foreach ($itemsParaGuardar as $item) {
                if ($canFinalize) {
                    $item['producto']->decrement('stock', $item['cantidad']);
                }

                DetalleVenta::create([
                    'venta_id' => $venta->id,
                    'producto_id' => $item['producto']->id,
                    'cantidad' => $item['cantidad'],
                    'precio' => $item['precio'],
                    'subtotal' => $item['subtotal'],
                ]);
            }

            if ($canFinalize) {
                $this->applyCreditMovementIfNeeded($venta, $cliente, $data);
            }

            DB::commit();

            return response()->json($venta->load(['cliente', 'vendedor', 'detalles.producto']), 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            $code = (int) $e->getCode();

            return response()->json(['message' => $e->getMessage()], $code >= 400 ? $code : 500);
        }
    }

    public function show(Venta $venta)
    {
        return response()->json($venta->load(['cliente', 'vendedor', 'detalles.producto']));
    }

    public function solicitarAutorizacionPrecio(Request $request)
    {
        $data = $request->validate([
            'producto_id' => ['required', 'exists:productos,id'],
            'precio_solicitado' => ['required', 'numeric', 'min:0'],
            'motivo' => ['required', 'string'],
        ]);

        $producto = Producto::findOrFail($data['producto_id']);
        $id = DB::table('autorizacion_precios')->insertGetId([
            'producto_id' => $producto->id,
            'vendedor_id' => $request->user()->id,
            'precio_normal' => $producto->precio,
            'precio_solicitado' => $data['precio_solicitado'],
            'diferencia' => round((float) $producto->precio - (float) $data['precio_solicitado'], 2),
            'motivo' => $data['motivo'],
            'estado' => 'pendiente',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(DB::table('autorizacion_precios')->find($id), 201);
    }

    public function aprobarAutorizacionPrecio(Request $request, int $id)
    {
        $data = $request->validate([
            'estado' => ['required', Rule::in(['aprobada', 'rechazada'])],
        ]);

        DB::table('autorizacion_precios')->where('id', $id)->update([
            'estado' => $data['estado'],
            'aprobado_por' => $request->user()->id,
            'aprobado_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(DB::table('autorizacion_precios')->find($id));
    }

    public function destroy(Venta $venta)
    {
        if ($venta->estado === 'cobrada') {
            return response()->json(['message' => 'Una venta cobrada no se elimina; debe anularse con auditoria.'], 422);
        }

        $venta->update(['estado' => 'anulada']);

        return response()->json(['message' => 'Venta anulada correctamente']);
    }

    public function boleta($id)
    {
        $venta = Venta::with('cliente', 'detalles.producto')->findOrFail($id);
        $subtotal = $venta->subtotal ?: ($venta->total / 1.18);
        $igv = $venta->igv ?: ($venta->total - $subtotal);

        $pdf = Pdf::loadView('boleta', compact('venta', 'subtotal', 'igv'));

        return $pdf->download('boleta.pdf');
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('fecha')) {
            $query->whereDate('created_at', $request->fecha);
        }

        if ($request->filled('desde')) {
            $query->whereDate('created_at', '>=', $request->desde);
        }

        if ($request->filled('hasta')) {
            $query->whereDate('created_at', '<=', $request->hasta);
        }

        foreach (['vendedor_id', 'cliente_id', 'comprobante_tipo', 'metodo_pago', 'estado'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->get($field));
            }
        }
    }

    private function scopeByRole($query, Request $request): void
    {
        $user = $request->user();

        if (!$user->hasPermission('ventas.aprobar') && !$user->hasPermission('caja.ver')) {
            $query->where('vendedor_id', $user->id);
        }
    }

    private function buildItems(array $productos): array
    {
        $subtotal = 0;
        $itemsParaGuardar = [];

        foreach ($productos as $item) {
            $producto = Producto::lockForUpdate()->findOrFail($item['producto_id']);
            $cantidad = (int) $item['cantidad'];
            $precio = (float) $item['precio'];

            if ($producto->stock < $cantidad) {
                throw new \Exception('Stock insuficiente de ' . $producto->nombre, 422);
            }

            $subtotalLinea = round($precio * $cantidad, 2);
            $subtotal += $subtotalLinea;
            $itemsParaGuardar[] = compact('producto', 'cantidad', 'precio', 'subtotalLinea') + ['subtotal' => $subtotalLinea];
        }

        return [$itemsParaGuardar, round($subtotal, 2)];
    }

    private function validateComprobante(string $tipo, Cliente $cliente): void
    {
        if ($tipo === 'factura' && (!$cliente->ruc || strlen($cliente->ruc) !== 11 || !$cliente->razon_social)) {
            throw new \Exception('La factura requiere RUC de 11 digitos y razon social.', 422);
        }

        if ($tipo === 'boleta' && $cliente->dni && strlen($cliente->dni) !== 8) {
            throw new \Exception('El DNI para boleta debe tener 8 digitos.', 422);
        }
    }

    private function applyCreditMovementIfNeeded(Venta $venta, Cliente $cliente, array $data): void
    {
        if (!in_array($data['metodo_pago'], ['credito', 'dinero_cuenta'], true)) {
            return;
        }

        $tipo = $data['metodo_pago'] === 'credito' ? 'credito' : 'cuenta';
        $credito = Credito::where('cliente_id', $cliente->id)->where('tipo', $tipo)->where('estado', 'activo')->latest()->first();

        if (!$credito) {
            throw new \Exception('El cliente no tiene linea activa para esta operacion.', 422);
        }

        if ($tipo === 'credito') {
            if (($credito->saldo_actual + $venta->total) > $credito->limite) {
                throw new \Exception('Credito excedido', 422);
            }
            $credito->saldo_actual = round($credito->saldo_actual + $venta->total, 2);
        } else {
            if ($credito->saldo_actual < $venta->total) {
                throw new \Exception('Saldo de cuenta insuficiente', 422);
            }
            $credito->saldo_actual = round($credito->saldo_actual - $venta->total, 2);
        }

        $credito->save();

        CreditoMovimiento::create([
            'credito_id' => $credito->id,
            'venta_id' => $venta->id,
            'tipo_movimiento' => 'cargo',
            'monto' => $venta->total,
            'saldo_resultante' => $credito->saldo_actual,
            'observacion' => 'Movimiento generado por venta',
            'user_id' => Auth::id(),
        ]);
    }

    private function estimatedProfit($ventas): ?float
    {
        $total = 0;
        $hasCosts = false;

        foreach ($ventas as $venta) {
            foreach ($venta->detalles as $detalle) {
                if ($detalle->producto?->costo === null) {
                    continue;
                }

                $hasCosts = true;
                $total += ((float) $detalle->precio - (float) $detalle->producto->costo) * (int) $detalle->cantidad;
            }
        }

        return $hasCosts ? round($total, 2) : null;
    }
}
