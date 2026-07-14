<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUsuarioRequest;
use App\Http\Requests\Admin\UpdateUsuarioRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Notifications\CredencialesTemporalesNotification;
use App\Services\ClienteCore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class UsuarioController extends Controller
{
    /**
     * Roles cuyo cargo vive físicamente en el Despacho del Alcalde — su
     * dependencia no es una decisión del admin, siempre es esa. El resto
     * de roles (SISBEN, JAC) son funcionarios externos sin dependencia.
     */
    private const ROLES_DESPACHO_ALCALDE = ['super_admin', 'alcalde', 'secretaria'];

    public function __construct(private readonly ClienteCore $core) {}

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
        $passwordTemporal = Str::password(12);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($passwordTemporal),
            'tipo_documento' => $data['tipo_documento'] ?? null,
            'numero_documento' => $data['numero_documento'] ?? null,
            'celular' => $data['celular'] ?? null,
            'dependencia_id' => $this->resolverDependencia($data['rol']),
            'activo' => true,
            'email_verified_at' => now(),
            'must_change_password' => true,
            'password_expires_at' => now()->addHours(24),
        ]);

        $user->syncRoles([$data['rol']]);

        $this->enviarCredenciales($user, $passwordTemporal);

        return (new UserResource($user->load(['roles'])))
            ->additional(['message' => 'Usuario creado correctamente. Se enviaron las credenciales de acceso por correo.'])
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateUsuarioRequest $request, User $usuario): JsonResponse
    {
        $data = $request->validated();

        $usuario->fill(collect($data)->except(['rol'])->all());

        if (! empty($data['rol'])) {
            $usuario->dependencia_id = $this->resolverDependencia($data['rol']);
            $usuario->save();
            $usuario->syncRoles([$data['rol']]);
        } else {
            $usuario->save();
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

    /** Alcalde/Secretaría/Super Admin → siempre Despacho del Alcalde; SISBEN/JAC → sin dependencia (externos). */
    private function resolverDependencia(string $rol): ?int
    {
        if (! in_array($rol, self::ROLES_DESPACHO_ALCALDE, true)) {
            return null;
        }

        try {
            return collect($this->core->dependencias())->firstWhere('nombre', 'Despacho del Alcalde')['id'] ?? null;
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /** No bloquea la creación del usuario si el correo falla — queda registrado para diagnóstico. */
    private function enviarCredenciales(User $user, string $passwordTemporal): void
    {
        try {
            Notification::route('mail', $user->email)
                ->notify(new CredencialesTemporalesNotification($user, $passwordTemporal));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
