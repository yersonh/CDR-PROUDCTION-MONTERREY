<?php

namespace App\Http\Resources;

use App\Models\SeguimientoEstado;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SeguimientoEstado */
class SeguimientoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'estado_anterior' => $this->estado_anterior?->value,
            'estado_nuevo' => $this->estado_nuevo->value,
            'estado_label' => $this->estado_nuevo->label(),
            'color' => $this->estado_nuevo->color(),
            'nota' => $this->nota,
            'actor' => $this->whenLoaded('actor', fn () => $this->actor?->name),
            'fecha' => $this->created_at,
        ];
    }
}
