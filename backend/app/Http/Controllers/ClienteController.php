<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ClienteController extends Controller
{
    private function asegurarAnonimo(): Cliente
    {
        return Cliente::firstOrCreate(
            ['tipo_cliente' => 'anonimo'],
            [
                'nombre' => 'Cliente Anónimo',
                'apodo' => 'Anónimo',
                'credito' => 0,
                'saldo' => 0,
                'activo' => true,
            ]
        );
    }

    private function subirArchivo(Request $request, string $campo, ?string $anterior = null): ?string
    {
        if (!$request->hasFile($campo)) {
            return $anterior;
        }

        if ($anterior) {
            Storage::disk('public')->delete($anterior);
        }

        return $request->file($campo)->store("clientes/{$campo}", 'public');
    }

    public function index()
    {
        $this->asegurarAnonimo();

        return response()->json(
            Cliente::with('creditos')->orderBy('nombre')->get()
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'apodo' => ['nullable', 'string', 'max:255'],
            'dni' => ['nullable', 'string', 'max:50', 'unique:clientes,dni'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255', 'unique:clientes,email'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'credito' => ['nullable', 'numeric', 'min:0'],
            'saldo' => ['nullable', 'numeric', 'min:0'],
            'tipo_cliente' => ['nullable', Rule::in(['anonimo', 'regular', 'credito', 'cuenta'])],
            'activo' => ['nullable', 'boolean'],
            'dni_imagen' => ['nullable', 'image', 'max:4096'],
            'firma_imagen' => ['nullable', 'image', 'max:4096'],
        ]);

        $cliente = Cliente::create([
            'nombre' => $request->nombre,
            'apodo' => $request->apodo,
            'dni' => $request->dni,
            'telefono' => $request->telefono,
            'email' => $request->email,
            'direccion' => $request->direccion,
            'credito' => $request->credito ?? 0,
            'saldo' => $request->saldo ?? 0,
            'tipo_cliente' => $request->tipo_cliente ?? 'regular',
            'activo' => $request->has('activo') ? (bool) $request->activo : true,
        ]);

        $cliente->dni_imagen = $this->subirArchivo($request, 'dni_imagen');
        $cliente->firma_imagen = $this->subirArchivo($request, 'firma_imagen');
        $cliente->save();

        return response()->json($cliente->load('creditos'), 201);
    }

    public function show($id)
    {
        $cliente = Cliente::with([
            'creditos' => fn ($q) => $q->latest(),
            'ventas.detalles.producto',
        ])->find($id);

        if (!$cliente) {
            return response()->json(['message' => 'Cliente no encontrado'], 404);
        }

        return response()->json($cliente);
    }

    public function update(Request $request, $id)
    {
        $cliente = Cliente::find($id);

        if (!$cliente) {
            return response()->json(['message' => 'Cliente no encontrado'], 404);
        }

        $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'apodo' => ['nullable', 'string', 'max:255'],
            'dni' => ['nullable', 'string', 'max:50', Rule::unique('clientes', 'dni')->ignore($cliente->id)],
            'telefono' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('clientes', 'email')->ignore($cliente->id)],
            'direccion' => ['nullable', 'string', 'max:255'],
            'credito' => ['nullable', 'numeric', 'min:0'],
            'saldo' => ['nullable', 'numeric', 'min:0'],
            'tipo_cliente' => ['nullable', Rule::in(['anonimo', 'regular', 'credito', 'cuenta'])],
            'activo' => ['nullable', 'boolean'],
            'dni_imagen' => ['nullable', 'image', 'max:4096'],
            'firma_imagen' => ['nullable', 'image', 'max:4096'],
        ]);

        $cliente->nombre = $request->nombre;
        $cliente->apodo = $request->apodo;
        $cliente->dni = $request->dni;
        $cliente->telefono = $request->telefono;
        $cliente->email = $request->email;
        $cliente->direccion = $request->direccion;
        $cliente->credito = $request->credito ?? $cliente->credito;
        $cliente->saldo = $request->saldo ?? $cliente->saldo;
        $cliente->tipo_cliente = $request->tipo_cliente ?? $cliente->tipo_cliente;
        $cliente->activo = $request->has('activo') ? (bool) $request->activo : $cliente->activo;

        $cliente->dni_imagen = $this->subirArchivo($request, 'dni_imagen', $cliente->dni_imagen);
        $cliente->firma_imagen = $this->subirArchivo($request, 'firma_imagen', $cliente->firma_imagen);

        $cliente->save();

        return response()->json($cliente->load('creditos'));
    }

    public function destroy($id)
    {
        $cliente = Cliente::find($id);

        if (!$cliente) {
            return response()->json(['message' => 'Cliente no encontrado'], 404);
        }

        if ($cliente->dni_imagen) {
            Storage::disk('public')->delete($cliente->dni_imagen);
        }

        if ($cliente->firma_imagen) {
            Storage::disk('public')->delete($cliente->firma_imagen);
        }

        $cliente->delete();

        return response()->json(['message' => 'Cliente eliminado correctamente']);
    }

    public function buscar(Request $request)
    {
        $q = trim((string) $request->get('q', $request->get('nombre', '')));

        $clientes = Cliente::where(function ($query) use ($q) {
            $query->where('nombre', 'like', "%{$q}%")
                ->orWhere('apodo', 'like', "%{$q}%")
                ->orWhere('dni', 'like', "%{$q}%");
        })
        ->orderBy('nombre')
        ->limit(20)
        ->get();

        return response()->json($clientes);
    }

    public function ventas($id)
    {
        $cliente = Cliente::with(['ventas.detalles.producto'])->find($id);

        if (!$cliente) {
            return response()->json(['message' => 'Cliente no encontrado'], 404);
        }

        $total = $cliente->ventas->sum('total');

        return response()->json([
            'cliente' => $cliente->nombre,
            'total_compras' => $total,
            'ventas' => $cliente->ventas,
        ]);
    }

    public function ventasPorFecha(Request $request, $id)
    {
        $cliente = Cliente::find($id);

        if (!$cliente) {
            return response()->json(['message' => 'Cliente no encontrado'], 404);
        }

        $fechaInicio = $request->fecha_inicio ?? now()->toDateString();
        $fechaFin = $request->fecha_fin ?? $fechaInicio;

        $ventas = $cliente->ventas()
            ->whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
            ->with('detalles.producto')
            ->latest()
            ->get();

        return response()->json($ventas);
    }

    public function ventasPorMes($id)
    {
        $cliente = Cliente::find($id);

        if (!$cliente) {
            return response()->json(['message' => 'Cliente no encontrado'], 404);
        }

        $ventas = $cliente->ventas()
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as mes, SUM(total) as total')
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        return response()->json($ventas);
    }

    public function historial($id)
    {
        $cliente = Cliente::with([
            'creditos' => fn ($q) => $q->latest(),
            'ventas' => fn ($q) => $q->latest(),
            'ventas.detalles.producto',
        ])->find($id);

        if (!$cliente) {
            return response()->json(['message' => 'Cliente no encontrado'], 404);
        }

        return response()->json([
            'cliente' => $cliente,
            'total_compras' => $cliente->ventas->sum('total'),
            'cantidad_ventas' => $cliente->ventas->count(),
            'ultima_compra' => optional($cliente->ventas->first())->created_at,
            'ventas' => $cliente->ventas,
            'creditos' => $cliente->creditos,
        ]);
    }

    public function creditos($id)
    {
        $cliente = Cliente::with('creditos')->find($id);

        if (!$cliente) {
            return response()->json(['message' => 'Cliente no encontrado'], 404);
        }

        return response()->json($cliente->creditos);
    }
}