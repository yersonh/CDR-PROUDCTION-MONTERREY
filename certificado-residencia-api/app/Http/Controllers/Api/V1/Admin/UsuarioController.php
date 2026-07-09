<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUsuarioRequest;
use App\Http\Requests\Admin\UpdateUsuarioRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query()->with(['roles'])->latest('id');

        if ($buscar = $request->string('buscar')->trim()->value()) {
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$buscar}%")
                ->orWhere('email', 'like', "%{$buscar}%")
                ->orWhere('numero_documento', 'like', "%{$buscar}%"));
        }

        if ($rol = $request->string('rol')->trim()->value()) {
            $query->role($rol);
        }

        return UserResource::collection($query->paginate($request->integer('per_page', 15)))->response();
    }

    public function store(StoreUsuarioRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'tipo_documento' => $data['tipo_documento'] ?? null,
            'numero_documento' => $data['numero_documento'] ?? null,
            'celular' => $data['celular'] ?? null,
            'dependencia_id' => $data['dependencia_id'] ?? null,
            'activo' => true,
            'email_verified_at' => now(),
        ]);

        $user->syncRoles([$data['rol']]);

        return (new UserResource($user->load(['roles'])))
            ->additional(['message' => 'Usuario creado correctamente.'])
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateUsuarioRequest $request, User $usuario): JsonResponse
    {
        $data = $request->validated();

        $usuario->fill(collect($data)->except(['password', 'rol'])->all());
        if (! empty($data['password'])) {
            $usuario->password = Hash::make($data['password']);
        }
        $usuario->save();

        if (! empty($data['rol'])) {
            $usuario->syncRoles([$data['rol']]);
        }

        return (new UserResource($usuario->load(['roles'])))
            ->additional(['message' => 'Usuario actualizado correctamente.'])
            ->response();
    }

    /** Activar / desactivar (no se elimina físicamente). */
    public function toggle(User $usuario): JsonResponse
    {
        $usuario->forceFill(['activo' => ! $usuario->activo])->save();

        return response()->json([
            'message' => $usuario->activo ? 'Usuario activado.' : 'Usuario desactivado.',
            'activo' => $usuario->activo,
        ]);
    }
}
