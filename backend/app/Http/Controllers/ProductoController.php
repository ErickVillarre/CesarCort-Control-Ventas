<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProductoController extends Controller
{
    private function tiposPermitidos(): array
    {
        return ['melamina', 'canto', 'accesorio', 'servicio', 'medelack'];
    }

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

    private function validarCamposPorTipo(Request $request)
    {
        $tipo = $request->tipo;

        if ($tipo !== 'medelack' && blank(trim((string) $request->nombre))) {
            return response()->json([
                'message' => 'Ingrese nombre'
            ], 422);
        }

        if ($tipo === 'melamina' && blank(trim((string) $request->espesor))) {
            return response()->json([
                'message' => 'Seleccione el espesor'
            ], 422);
        }

        if ($tipo === 'canto') {
            if (blank(trim((string) $request->canto_tipo)) || blank(trim((string) $request->canto_ancho))) {
                return response()->json([
                    'message' => 'Complete tipo y medida del canto'
                ], 422);
            }
        }

        if ($tipo === 'medelack' && blank(trim((string) $request->color))) {
            return response()->json([
                'message' => 'Ingrese color'
            ], 422);
        }

        if ($tipo === 'servicio' && blank(trim((string) $request->nombre))) {
            return response()->json([
                'message' => 'Ingrese nombre del servicio'
            ], 422);
        }

        return null;
    }

    public function index()
    {
        return Producto::latest()->get();
    }

    public function store(Request $request)
    {
        if ($resp = $this->validarAdmin()) {
            return $resp;
        }

        $request->validate([
            'tipo' => ['required', Rule::in($this->tiposPermitidos())],
            'nombre' => ['nullable', 'string', 'max:255'],
            'precio' => ['required', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'espesor' => ['nullable', Rule::in(['18mm', '15mm'])],
            'canto_tipo' => ['nullable', Rule::in(['grueso', 'delgado'])],
            'canto_ancho' => ['nullable', Rule::in(['normal', 'ancho'])],
            'color' => ['nullable', 'string', 'max:255'],
        ]);

        if ($resp = $this->validarCamposPorTipo($request)) {
            return $resp;
        }

        $nombreFinal = $this->construirNombre($request);

        $yaExiste = Producto::where('nombre', $nombreFinal)->exists();
        if ($yaExiste) {
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
        if ($resp = $this->validarAdmin()) {
            return $resp;
        }

        $producto = Producto::find($id);

        if (!$producto) {
            return response()->json([
                'message' => 'Producto no encontrado'
            ], 404);
        }

        $request->validate([
            'tipo' => ['required', Rule::in($this->tiposPermitidos())],
            'nombre' => ['nullable', 'string', 'max:255'],
            'precio' => ['required', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'espesor' => ['nullable', Rule::in(['18mm', '15mm'])],
            'canto_tipo' => ['nullable', Rule::in(['grueso', 'delgado'])],
            'canto_ancho' => ['nullable', Rule::in(['normal', 'ancho'])],
            'color' => ['nullable', 'string', 'max:255'],
        ]);

        if ($resp = $this->validarCamposPorTipo($request)) {
            return $resp;
        }

        $nombreFinal = $this->construirNombre($request);

        $yaExiste = Producto::where('nombre', $nombreFinal)
            ->where('id', '!=', $producto->id)
            ->exists();

        if ($yaExiste) {
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
        if ($resp = $this->validarAdmin()) {
            return $resp;
        }

        $producto = Producto::find($id);

        if (!$producto) {
            return response()->json([
                'message' => 'Producto no encontrado'
            ], 404);
        }

        $producto->delete();

        return response()->json([
            'message' => 'Producto eliminado correctamente'
        ]);
    }
}