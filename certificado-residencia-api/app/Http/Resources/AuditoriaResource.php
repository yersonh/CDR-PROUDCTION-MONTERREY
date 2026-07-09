<?php

namespace App\Http\Resources;

use App\Models\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Auditoria */
class AuditoriaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'accion' => $this->accion,
            'descripcion' => $this->descripcion,
            'usuario' => $this->whenLoaded('user', fn () => $this->user?->name),
            'entidad' => $this->auditable_type ? class_basename($this->auditable_type) : null,
            'entidad_id' => $this->auditable_id,
            'ip' => $this->ip,
            'navegador' => $this->user_agent,
            'metodo' => $this->metodo,
            'datos_antes' => $this->datos_antes,
            'datos_despues' => $this->datos_despues,
            'fecha' => $this->created_at,
        ];
    }
}
