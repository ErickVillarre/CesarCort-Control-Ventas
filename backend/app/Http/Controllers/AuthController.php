<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $user = User::with(['role.permissions', 'employee'])
            ->where('email', $data['email'])
            ->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json([
                'message' => 'Credenciales incorrectas'
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'El acceso de este usuario esta desactivado.'
            ], 403);
        }

        $user->forceFill(['last_login_at' => now()])->save();
        $token = $user->createToken($data['remember'] ?? false ? 'remembered-token' : 'token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'must_change_password' => $user->must_change_password,
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user()->load(['role.permissions', 'employee']),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Sesion cerrada correctamente']);
    }

    public function changePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)->mixedCase()->numbers()->symbols(),
            ],
        ]);

        $user = $request->user();

        if (!Hash::check($data['current_password'], $user->password)) {
            return response()->json([
                'message' => 'La contrasena actual no es correcta.',
            ], 422);
        }

        if (Hash::check($data['password'], $user->getOriginal('password'))) {
            return response()->json([
                'message' => 'No puedes reutilizar tu contrasena actual.',
            ], 422);
        }

        $user->forceFill([
            'password' => Hash::make($data['password']),
            'must_change_password' => false,
        ])->save();

        $currentToken = $user->currentAccessToken();

        if ($currentToken) {
            $user->tokens()->where('id', '!=', $currentToken->id)->delete();
        }

        return response()->json([
            'message' => 'Contrasena actualizada correctamente.',
            'user' => $user->fresh(['role.permissions', 'employee']),
        ]);
    }
}
