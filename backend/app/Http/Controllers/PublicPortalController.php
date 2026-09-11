<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicPortalController extends Controller
{
    public function demoAccess(Request $request)
    {
        $data = $request->validate([
            'usuario' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $expectedUser = (string) config('services.landing_demo.user');
        $expectedPassword = (string) config('services.landing_demo.password');

        if ($expectedUser === '' || $expectedPassword === '') {
            return response()->json(['message' => 'Acceso demo no configurado'], 503);
        }

        if (!hash_equals($expectedUser, $data['usuario']) || !hash_equals($expectedPassword, $data['password'])) {
            return response()->json(['message' => 'Usuario o contraseña incorrectos'], 401);
        }

        return response()->json([
            'redirect_url' => rtrim((string) config('services.landing_demo.crm_login_url'), '/'),
        ]);
    }

    public function storeSolicitud(Request $request)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:160'],
            'telefono' => ['required', 'string', 'max:40'],
            'dni_ruc' => ['nullable', 'string', 'max:11'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'producto' => ['nullable', 'string', 'max:180'],
            'cantidad_aproximada' => ['nullable', 'integer', 'min:1'],
            'mensaje' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user('sanctum');
        $cliente = $user?->cliente ?: ($data['email'] ?? null
            ? Cliente::where('email', $data['email'])->first()
            : null);

        $solicitudId = DB::table('public_solicitudes')->insertGetId([
            'user_id' => $user?->id,
            'cliente_id' => $cliente?->id,
            'nombre' => $data['nombre'],
            'email' => $data['email'] ?? $user?->email,
            'telefono' => $data['telefono'],
            'dni_ruc' => $data['dni_ruc'] ?? null,
            'direccion' => $data['direccion'] ?? null,
            'producto' => $data['producto'] ?? null,
            'cantidad_aproximada' => $data['cantidad_aproximada'] ?? 1,
            'mensaje' => $data['mensaje'] ?? null,
            'estado' => 'pendiente',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('stock_alertas')->insert([
            'producto_id' => null,
            'solicitado_por' => null,
            'tipo' => 'solicitado_cliente',
            'cantidad_aproximada' => $data['cantidad_aproximada'] ?? 1,
            'observacion' => 'Solicitud publica #' . $solicitudId . ': ' . ($data['producto'] ?? 'Sin producto especifico'),
            'estado' => 'pendiente',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(DB::table('public_solicitudes')->find($solicitudId), 201);
    }

    public function solicitudes(Request $request)
    {
        $user = $request->user();

        $query = DB::table('public_solicitudes')->latest();

        if (!$user->hasPermission('web_requests.view') && $user->role_name === 'cliente') {
            $query->where('user_id', $user->id);
        }

        return response()->json($query->get());
    }
}
