<?php

namespace App\Http\Controllers;

use App\Models\Credito;
use App\Models\CreditoMovimiento;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CreditoController extends Controller
{
    private function validarAdmin()
    {
        $user = Auth::user();

        if (!$user || $user->rol !== 'admin') {
            return response()->json([
                'message' => 'Solo el administrador puede realizar esta acción'
            ], 403);
        }

        return null;
    }

    public function index(Request $request)
    {
        $query = Credito::with(['cliente', 'creador'])->latest();

        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        if ($resp = $this->validarAdmin()) return $resp;

        $request->validate([
            'cliente_id' => ['required', 'exists:clientes,id'],
            'tipo' => ['required', Rule::in(['credito', 'cuenta'])],
            'limite' => ['required', 'numeric', 'min:0'],
            'saldo_actual' => ['nullable', 'numeric', 'min:0'],
            'fecha_vencimiento' => ['nullable', 'date'],
            'estado' => ['nullable', Rule::in(['activo', 'cerrado', 'vencido'])],
            'observacion' => ['nullable', 'string'],
        ]);

        $credito = Credito::create([
            'cliente_id' => $request->cliente_id,
            'tipo' => $request->tipo,
            'limite' => $request->limite,
            'saldo_actual' => $request->saldo_actual ?? 0,
            'fecha_vencimiento' => $request->fecha_vencimiento,
            'estado' => $request->estado ?? 'activo',
            'observacion' => $request->observacion,
            'creado_por' => Auth::id(),
        ]);

        return response()->json($credito->load('cliente'), 201);
    }

    public function show($id)
    {
        $credito = Credito::with(['cliente', 'movimientos.usuario', 'movimientos.venta'])->find($id);

        if (!$credito) {
            return response()->json(['message' => 'Crédito no encontrado'], 404);
        }

        return response()->json($credito);
    }

    public function update(Request $request, $id)
    {
        if ($resp = $this->validarAdmin()) return $resp;

        $credito = Credito::find($id);

        if (!$credito) {
            return response()->json(['message' => 'Crédito no encontrado'], 404);
        }

        $request->validate([
            'cliente_id' => ['required', 'exists:clientes,id'],
            'tipo' => ['required', Rule::in(['credito', 'cuenta'])],
            'limite' => ['required', 'numeric', 'min:0'],
            'saldo_actual' => ['nullable', 'numeric', 'min:0'],
            'fecha_vencimiento' => ['nullable', 'date'],
            'estado' => ['nullable', Rule::in(['activo', 'cerrado', 'vencido'])],
            'observacion' => ['nullable', 'string'],
        ]);

        $credito->update([
            'cliente_id' => $request->cliente_id,
            'tipo' => $request->tipo,
            'limite' => $request->limite,
            'saldo_actual' => $request->saldo_actual ?? $credito->saldo_actual,
            'fecha_vencimiento' => $request->fecha_vencimiento,
            'estado' => $request->estado ?? $credito->estado,
            'observacion' => $request->observacion,
        ]);

        return response()->json($credito->fresh()->load('cliente'));
    }

    public function destroy($id)
    {
        if ($resp = $this->validarAdmin()) return $resp;

        $credito = Credito::find($id);

        if (!$credito) {
            return response()->json(['message' => 'Crédito no encontrado'], 404);
        }

        $credito->delete();

        return response()->json(['message' => 'Crédito eliminado correctamente']);
    }

    public function abonar(Request $request, $id)
    {
        if ($resp = $this->validarAdmin()) return $resp;

        $credito = Credito::with('cliente')->find($id);

        if (!$credito) {
            return response()->json(['message' => 'Crédito no encontrado'], 404);
        }

        $request->validate([
            'monto' => ['required', 'numeric', 'min:0.01'],
            'observacion' => ['nullable', 'string'],
        ]);

        if ($credito->tipo === 'credito') {
            $credito->saldo_actual = max(0, (float) $credito->saldo_actual - (float) $request->monto);
            $tipoMovimiento = 'abono';
        } else {
            $credito->saldo_actual = (float) $credito->saldo_actual + (float) $request->monto;
            $tipoMovimiento = 'ajuste';
        }

        $credito->save();

        CreditoMovimiento::create([
            'credito_id' => $credito->id,
            'tipo_movimiento' => $tipoMovimiento,
            'monto' => $request->monto,
            'saldo_resultante' => $credito->saldo_actual,
            'observacion' => $request->observacion,
            'user_id' => Auth::id(),
        ]);

        return response()->json($credito->fresh()->load('cliente'));
    }
}