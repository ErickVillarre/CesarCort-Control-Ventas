<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EmpleadoController extends Controller
{
    public function index()
    {
        return response()->json(
            Empleado::with('user.role')->orderBy('nombre')->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'cargo' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:empleados,email'],
            'telefono' => ['nullable', 'string', 'max:40'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $data['activo'] = $request->boolean('activo', true);

        return response()->json(Empleado::create($data), 201);
    }

    public function update(Request $request, Empleado $empleado)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'cargo' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('empleados', 'email')->ignore($empleado->id)],
            'telefono' => ['nullable', 'string', 'max:40'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $data['activo'] = $request->boolean('activo', true);
        $empleado->update($data);

        return response()->json($empleado->fresh('user.role'));
    }

    public function habilitarAcceso(Request $request, Empleado $empleado)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($empleado->user?->id)],
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        $role = Role::findOrFail($data['role_id']);
        $legacyRole = in_array($role->name, ['admin', 'vendedor'], true) ? $role->name : 'vendedor';

        $user = $empleado->user ?: new User();
        $isNew = !$user->exists;

        $user->fill([
            'name' => $empleado->nombre,
            'email' => $data['email'],
            'employee_id' => $empleado->id,
            'role_id' => $role->id,
            'rol' => $legacyRole,
            'is_active' => true,
            'must_change_password' => $isNew ? true : $user->must_change_password,
        ]);

        if ($isNew) {
            $user->password = Hash::make(Str::password(32));
        }

        $user->save();

        return response()->json($empleado->fresh('user.role'));
    }

    public function desactivarAcceso(Empleado $empleado)
    {
        if (!$empleado->user) {
            return response()->json(['message' => 'El empleado no tiene acceso activo.'], 422);
        }

        $empleado->user->update(['is_active' => false]);
        $empleado->user->tokens()->delete();

        return response()->json($empleado->fresh('user.role'));
    }
}
