<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Services\ClienteCore;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'tipo_documento' => $this->tipo_documento,
            'numero_documento' => $this->numero_documento,
            'celular' => $this->celular,
            'activo' => $this->activo,
            'dependencia' => $this->when($this->dependencia_id !== null, function () {
                try {
                    $dependencia = app(ClienteCore::class)->dependencia($this->dependencia_id);
                } catch (\Throwable) {
                    return null;
                }

                return $dependencia ? ['id' => $dependencia['id'], 'nombre' => $dependencia['nombre']] : null;
            }),
            'roles' => $this->getRoleNames(),
            'permisos' => $this->getAllPermissions()->pluck('name'),
            'tiene_firma' => ! empty($this->firma_path),
            'tiene_foto' => ! empty($this->foto_path),
            'funcionario' => $this->when($this->numero_documento !== null, function () {
                try {
                    $funcionario = app(ClienteCore::class)->funcionario($this->numero_documento);
                } catch (\Throwable) {
                    return null;
                }

                if (! $funcionario) {
                    return null;
                }

                return [
                    'cargo' => $funcionario['cargo'] ?? null,
                    'dependencia' => $funcionario['dependencia']['nombre'] ?? null,
                    'telefono' => $funcionario['persona']['telefono'] ?? null,
                    'correo_institucional' => $funcionario['persona']['email'] ?? null,
                    'fecha_vinculacion' => $funcionario['fecha_vinculacion'] ?? null,
                ];
            }),
            'last_login_at' => $this->last_login_at,
        ];
    }
}
