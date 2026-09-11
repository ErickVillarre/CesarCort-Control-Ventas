<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockAlertaController extends Controller
{
    public function index()
    {
        return response()->json(
            DB::table('stock_alertas')
                ->leftJoin('productos', 'productos.id', '=', 'stock_alertas.producto_id')
                ->select('stock_alertas.*', 'productos.nombre as producto')
                ->latest('stock_alertas.created_at')
                ->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'producto_id' => ['nullable', 'exists:productos,id'],
            'tipo' => ['required', 'in:agotado,stock_bajo,solicitado_cliente'],
            'cantidad_aproximada' => ['required', 'integer', 'min:1'],
            'observacion' => ['nullable', 'string'],
        ]);

        $data['solicitado_por'] = $request->user()->id;
        $data['created_at'] = now();
        $data['updated_at'] = now();

        $id = DB::table('stock_alertas')->insertGetId($data);

        return response()->json(DB::table('stock_alertas')->find($id), 201);
    }

    public function publicStore(Request $request)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'telefono' => ['required', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:160'],
            'producto' => ['nullable', 'string', 'max:180'],
            'cantidad_aproximada' => ['nullable', 'integer', 'min:1'],
            'mensaje' => ['nullable', 'string', 'max:1000'],
        ]);

        $observacion = collect([
            'Solicitud desde landing page',
            'Cliente: ' . $data['nombre'],
            'Telefono: ' . $data['telefono'],
            isset($data['email']) ? 'Email: ' . $data['email'] : null,
            isset($data['producto']) ? 'Producto: ' . $data['producto'] : null,
            isset($data['mensaje']) ? 'Mensaje: ' . $data['mensaje'] : null,
        ])->filter()->implode("\n");

        $id = DB::table('stock_alertas')->insertGetId([
            'producto_id' => null,
            'tipo' => 'solicitado_cliente',
            'cantidad_aproximada' => $data['cantidad_aproximada'] ?? 1,
            'observacion' => $observacion,
            'estado' => 'pendiente',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(DB::table('stock_alertas')->find($id), 201);
    }
}
