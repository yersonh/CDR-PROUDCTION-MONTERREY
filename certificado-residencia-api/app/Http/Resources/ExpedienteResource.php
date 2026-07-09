<?php

namespace App\Http\Resources;

use App\Models\Expediente;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Expediente */
class ExpedienteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'documentos' => DocumentoResource::collection($this->whenLoaded('documentos')),
            'creado_at' => $this->created_at,
        ];
    }
}
