<?php

namespace App\Http\Controllers;

use App\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->orderBy('label')->get();
        $permissions = $roles
            ->flatMap(fn (Role $role) => $role->permissions)
            ->unique('name')
            ->sortBy(['category', 'label'])
            ->values();

        return response()->json([
            'roles' => $roles,
            'permissions' => $permissions,
            'matrix' => $roles->map(fn (Role $role) => [
                'role' => $role->only(['id', 'name', 'label', 'description']),
                'permissions' => $permissions->mapWithKeys(fn ($permission) => [
                    $permission->name => $role->permissions->contains('name', $permission->name),
                ]),
            ])->values(),
        ]);
    }
}
