<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $producto= Producto::all();
        return response()->json($producto);
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
        $productos = Producto::create([
            'nombre'=> $request->nombre,
            'precio'=> $request->precio,
            'stock'=> $request->stock
        ]);
        return response()->json($productos);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
         $producto = Producto::find($id);

         if (!$producto) {
            return response()->json([
            'message' => 'Producto no encontrado'
        ], 404);
    }

        return response()->json($producto);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Producto $producto)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $producto= Producto::find($id);
        if (!$producto){
            return response()->json([
                'message' => 'Producto no encontrado'
            ],404);
        }
        $producto->update([
            'nombre'=>$request->nombre,
            'precio'=>$request->precio,
            'stock'=>$request->stock
        ]);
        return response()->json($producto);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
         if (auth()->user()->rol !== 'admin') {
        return response()->json([
            'message' => 'No autorizado'
        ], 403);
        }

        $producto= Producto::find($id);
        if(!$producto) {
            return response()->json([
                'message'=>'Producto no encontrado'
            ],404);
        }
        $producto->delete();
        return response()->json([
            'message'=>'Producto eliminador correctamente'
        ]);
    }

    public function buscar(Request $request)
    {
        if (!$request->nombre) {
        return response()->json([
            'message' => 'Debe enviar un nombre'
        ], 400);
    }

     $productos = Producto::where('nombre', 'like', '%' . $request->nombre . '%')
        ->get();

    return response()->json($productos);
    }

    public function autocomplete(Request $request)
    {
    $productos = Producto::where('nombre', 'like', '%' . $request->nombre . '%')
        ->limit(5)
        ->get(['id', 'nombre']);

    return response()->json($productos);
    }
}
