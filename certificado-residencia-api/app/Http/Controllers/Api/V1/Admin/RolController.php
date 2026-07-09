<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;

class RolController extends Controller
{
    /** Listado de roles con sus permisos y conteo de usuarios. */
    public function index(): JsonResponse
    {
        $roles = Role::with('permissions')->orderBy('name')->get();

        return response()->json([
            'data' => $roles->map(fn (Role $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'usuarios' => $r->users()->count(),
                'permisos' => $r->permissions->pluck('name')->values(),
            ]),
        ]);
    }
}
