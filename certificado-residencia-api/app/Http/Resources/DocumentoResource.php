<?php

namespace App\Http\Resources;

use App\Models\ExpedienteDocumento;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ExpedienteDocumento */
class DocumentoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'nombre_original' => $this->nombre_original,
            'mime' => $this->mime,
            'size' => $this->size,
            'hash' => $this->hash,
            'es_certificado' => $this->es_certificado,
            'version' => $this->version,
            'vigente' => $this->vigente,
            'subido_at' => $this->created_at,
        ];
    }
}
