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
}
