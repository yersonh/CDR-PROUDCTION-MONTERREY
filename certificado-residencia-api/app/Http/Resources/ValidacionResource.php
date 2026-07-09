<?php

namespace App\Http\Resources;

use App\Models\Validacion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Validacion */
class ValidacionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'resultado' => $this->resultado?->value,
            'resultado_label' => $this->resultado?->label(),
            'observacion' => $this->observacion,
            'meta' => $this->meta,
            'documento' => new DocumentoResource($this->whenLoaded('documento')),
            'validado_por' => $this->whenLoaded('validadoPor', fn () => $this->validadoPor?->name),
            'validado_at' => $this->validado_at,
        ];
    }
}
