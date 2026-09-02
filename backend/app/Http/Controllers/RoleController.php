<?php

namespace App\Http\Controllers;

use App\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        return response()->json(
            Role::with('permissions')->orderBy('label')->get()
        );
    }
}
