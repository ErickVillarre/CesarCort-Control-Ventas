<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $clientes = Cliente::all();
        return response()->json($clientes);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|unique:clientes,nombre',
            'credito' => 'required|numeric'
     ]);

         $cliente = Cliente::create([
            'nombre' => $request->nombre,
            'credito' => $request->credito,
            'saldo' => 0
    ]);

    return response()->json($cliente);
}

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $cliente = Cliente::find($id);
        if(!$cliente){
            return response()->json([
                'message' => 'Cliente no encontrado'
            ],404);
        }
        return response()->json($cliente);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(cliente $cliente)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, cliente $cliente)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(cliente $cliente)
    {
        //
    }

    public function ventas($id)
    {
        $cliente = Cliente::with('ventas.producto')->find($id);

        if (!$cliente) {
            return response()->json([
                'message' => 'Cliente no encontrado'
        ], 404);
    }

        $total = $cliente->ventas->sum('total');

        return response()->json([
            'cliente' => $cliente->nombre,
            'total_compras' => $total,
            'ventas' => $cliente->ventas
        ]);
    }

    public function ventasPorFecha(Request $request, $id)
    {
        $cliente = Cliente::find($id);

        if (!$cliente) {
            return response()->json([
                'message' => 'Cliente no encontrado'
        ], 404);
        }

        $ventas = $cliente->ventas()
        ->whereBetween('created_at', [
            $request->fecha_inicio,
            $request->fecha_fin
        ])
        ->with('producto')
        ->get();

        return response()->json($ventas);
    }

    public function ventasPorMes($id)
    {
        $cliente = Cliente::find($id);

        if (!$cliente) {
            return response()->json([
            'message' => 'Cliente no encontrado'
        ], 404);
    }

        $ventas = $cliente->ventas()
            ->selectRaw('MONTH(created_at) as mes, SUM(total) as total')
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

    return response()->json($ventas);
    }

    public function buscar(Request $request)
    {
        $clientes = Cliente::where('nombre', 'like', "%{$request->nombre}%")->get();

    return response()->json($clientes);
    }

    public function historial($id)
    {
         $cliente = Cliente::with([
            'ventas' => function ($query) {
                $query->orderBy('created_at', 'desc');
            },
                'ventas.detalles.producto'
        ])->find($id);

        if (!$cliente) {
             return response()->json([
                'message' => 'Cliente no encontrado'
        ], 404);
        }

   
        $totalComprado = $cliente->ventas->sum('total');
        $cantidadVentas = $cliente->ventas->count();
        $ultimaCompra = optional($cliente->ventas->last())->created_at;
        $estado = $this->calcularEstado($cliente);

         return response()->json([
            'cliente' => $cliente->nombre,
            'credito_maximo' => $cliente->credito,
            'saldo_actual' => $cliente->saldo,
            'estado_cliente' => $estado,
            'total_compras' => $totalComprado,
            'cantidad_ventas' => $cantidadVentas,
            'ultima_compra' => $ultimaCompra,

            'ventas' => $cliente->ventas,
        ]);
        
    }

 private function calcularEstado($cliente)
    {
        if ($cliente->saldo == 0) {
        return 'buen_pagador';
    }

    $porcentaje = ($cliente->saldo / $cliente->credito) * 100;

    if ($porcentaje <= 50) {
        return 'en_riesgo';
    }

    return 'moroso';
    }
}
