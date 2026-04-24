<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\Cliente;
use App\Models\Producto;
use Illuminate\Http\Request;
use App\Models\DetalleVenta;
use Barryvdh\DomPDF\Facade\Pdf;

class VentaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $ventas = Venta::with(['cliente','producto'])
        ->latest()
    ->get();

        return response()->json($ventas);
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
            $cliente = Cliente::find($request->cliente_id);

            if (!$cliente) {
                return response()->json([
                 'message' => 'Cliente no encontrado'
            ], 404);
             }

            $total = 0;
            $detalles = [];

    // recorrer productos 
         foreach ($request->productos as $item) {

            $producto = Producto::find($item['producto_id']);

            if (!$producto) {
                return response()->json([
                 'message' => 'Producto no encontrado'
            ], 404);
            }

        //  validar stock
            if ($producto->stock < $item['cantidad']) {
                return response()->json([
                    'message' => 'Stock insuficiente de ' . $producto->nombre
                ], 400);
             }

             $subtotal = $producto->precio * $item['cantidad'];

             $detalles[] = [
                'producto' => $producto,
                'cantidad' => $item['cantidad'],
                'precio' => $producto->precio,
                 'subtotal' => $subtotal
            ];

            $total += $subtotal;
        }

    // LÓGICA DE NEGOCIO 
            if ($request->tipo_operacion === 'saldo') {

            if ($cliente->saldo < $total) {
                return response()->json([
                    'message' => 'Saldo insuficiente'
                ], 400);
            }

            $cliente->saldo -= $total;
            }

             if ($request->tipo_operacion === 'credito') {

            if ($cliente->saldo + $total > $cliente->credito) {
                return response()->json([
                 'message' => 'Crédito excedido'
             ], 400);
            }

            $cliente->saldo += $total;
            }

    //guardar cliente
            $cliente->save();

    // crear venta 
            $venta = Venta::create([
            'cliente_id' => $cliente->id,
            'total' => $total,
            'metodo_pago' => $request->metodo_pago,
            'tipo_operacion' => $request->tipo_operacion
            ]);

    //  guardar detalles/descontar stock
        foreach ($detalles as $item) {

             $producto = $item['producto'];

        // descontar stock
            $producto->stock -= $item['cantidad'];
             $producto->save();

            DetalleVenta::create([
                'venta_id' => $venta->id,
                'producto_id' => $producto->id,
                'cantidad' => $item['cantidad'],
                'precio' => $item['precio'],
                'subtotal' => $item['subtotal']
            ]);
    }

    return response()->json(
        $venta->load('detalles.producto')
    );
    }

    /**
     * Display the specified resource.
     */
    public function show(Venta $venta)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Venta $venta)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Venta $venta)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Venta $venta)
    {
        //
    }

public function boleta($id)
    {
   $venta = Venta::with('cliente', 'detalles.producto')->findOrFail($id);



    $subtotal = $venta->total / 1.18;
    $igv = $venta->total - $subtotal;

    $pdf = Pdf::loadView('boleta', compact('venta', 'subtotal', 'igv'));

    return $pdf->download('boleta.pdf');
    }
}
