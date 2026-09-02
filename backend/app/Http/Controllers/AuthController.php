<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $email = trim((string) $request->email);
        $password = (string) $request->password;

        Log::info('Login attempt', [
            'email' => $email,
            'has_password' => $password !== '',
        ]);

        $user = User::where('email', $email)->first();

        if ($user) {
            Log::info('User found for login', [
                'email' => $user->email,
                'stored_hash_prefix' => substr($user->password, 0, 20),
                'password_match' => Hash::check($password, $user->password),
            ]);
        } else {
            Log::warning('User not found for login', ['email' => $email]);
        }

        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json([
                'message' => 'Credenciales incorrectas'
            ], 401);
        }

        $token = $user->createToken('token')->plainTextToken;

        Log::info('Login successful', ['email' => $email]);

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }
}
