<?php

namespace App\Http\Resources;

use App\Models\User;
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
            'dependencia' => $this->whenLoaded('dependencia', fn () => [
                'id' => $this->dependencia?->id,
                'nombre' => $this->dependencia?->nombre,
            ]),
            'roles' => $this->getRoleNames(),
            'permisos' => $this->getAllPermissions()->pluck('name'),
            'tiene_firma' => ! empty($this->firma_path),
            'last_login_at' => $this->last_login_at,
        ];
    }
}
