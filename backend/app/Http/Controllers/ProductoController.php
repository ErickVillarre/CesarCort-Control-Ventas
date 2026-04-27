<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProductoController extends Controller
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

    private function construirNombre(Request $request): string
    {
        $tipo = $request->tipo;
        $nombre = trim((string) $request->nombre);
        $espesor = trim((string) ($request->espesor ?: '18mm'));
        $cantoTipo = trim((string) $request->canto_tipo);
        $cantoAncho = trim((string) $request->canto_ancho);
        $color = trim((string) $request->color);

        return match ($tipo) {
            'melamina' => "Melamina {$nombre} {$espesor}",
            'canto' => "Canto {$nombre} {$cantoTipo} {$cantoAncho}",
            'accesorio' => "Accesorio {$nombre}",
            'servicio' => "Servicio {$nombre}",
            'medelack' => "Medelack {$color}",
            default => $nombre,
        };
    }

    public function index()
    {
        return Producto::latest()->get();
    }

    public function show(Producto $producto)
    {
        return response()->json($producto);
    }

    public function store(Request $request)
    {
        if ($resp = $this->validarAdmin()) return $resp;

        $request->validate([
            'tipo' => ['required', Rule::in(['melamina', 'canto', 'accesorio', 'servicio', 'medelack'])],
            'nombre' => ['nullable', 'string', 'max:255'],
            'precio' => ['required', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'espesor' => ['nullable', Rule::in(['18mm', '15mm'])],
            'canto_tipo' => ['nullable', Rule::in(['grueso', 'delgado'])],
            'canto_ancho' => ['nullable', Rule::in(['normal', 'ancho'])],
            'color' => ['nullable', 'string', 'max:255'],
        ]);

        $nombreFinal = $this->construirNombre($request);

        if (Producto::where('nombre', $nombreFinal)->exists()) {
            return response()->json([
                'message' => 'Ya existe un producto con ese nombre'
            ], 422);
        }

        $producto = Producto::create([
            'nombre' => $nombreFinal,
            'precio' => $request->precio,
            'stock' => $request->tipo === 'servicio' ? 0 : (int) $request->stock,
            'tipo' => $request->tipo,
            'espesor' => $request->tipo === 'melamina' ? ($request->espesor ?: '18mm') : null,
            'canto_tipo' => $request->tipo === 'canto' ? $request->canto_tipo : null,
            'canto_ancho' => $request->tipo === 'canto' ? $request->canto_ancho : null,
            'color' => $request->tipo === 'medelack' ? $request->color : null,
        ]);

        return response()->json($producto, 201);
    }

    public function update(Request $request, $id)
    {
        if ($resp = $this->validarAdmin()) return $resp;

        $producto = Producto::find($id);

        if (!$producto) {
            return response()->json(['message' => 'Producto no encontrado'], 404);
        }

        $request->validate([
            'tipo' => ['required', Rule::in(['melamina', 'canto', 'accesorio', 'servicio', 'medelack'])],
            'nombre' => ['nullable', 'string', 'max:255'],
            'precio' => ['required', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'espesor' => ['nullable', Rule::in(['18mm', '15mm'])],
            'canto_tipo' => ['nullable', Rule::in(['grueso', 'delgado'])],
            'canto_ancho' => ['nullable', Rule::in(['normal', 'ancho'])],
            'color' => ['nullable', 'string', 'max:255'],
        ]);

        $nombreFinal = $this->construirNombre($request);

        if (
            Producto::where('nombre', $nombreFinal)
                ->where('id', '!=', $producto->id)
                ->exists()
        ) {
            return response()->json([
                'message' => 'Ya existe un producto con ese nombre'
            ], 422);
        }

        $producto->update([
            'nombre' => $nombreFinal,
            'precio' => $request->precio,
            'stock' => $request->tipo === 'servicio' ? 0 : (int) $request->stock,
            'tipo' => $request->tipo,
            'espesor' => $request->tipo === 'melamina' ? ($request->espesor ?: '18mm') : null,
            'canto_tipo' => $request->tipo === 'canto' ? $request->canto_tipo : null,
            'canto_ancho' => $request->tipo === 'canto' ? $request->canto_ancho : null,
            'color' => $request->tipo === 'medelack' ? $request->color : null,
        ]);

        return response()->json($producto);
    }

    public function destroy($id)
    {
        if ($resp = $this->validarAdmin()) return $resp;

        $producto = Producto::find($id);

        if (!$producto) {
            return response()->json(['message' => 'Producto no encontrado'], 404);
        }

        $producto->delete();

        return response()->json(['message' => 'Producto eliminado correctamente']);
    }

    public function buscar(Request $request)
    {
        $q = trim((string) $request->get('q', $request->get('nombre', '')));

        return response()->json(
            Producto::where('nombre', 'like', "%{$q}%")
                ->orderBy('nombre')
                ->limit(20)
                ->get()
        );
    }

    public function autocomplete(Request $request)
    {
        $q = trim((string) $request->get('q', $request->get('term', '')));

        return response()->json(
            Producto::where('nombre', 'like', "%{$q}%")
                ->orderBy('nombre')
                ->limit(10)
                ->get()
        );
    }
}