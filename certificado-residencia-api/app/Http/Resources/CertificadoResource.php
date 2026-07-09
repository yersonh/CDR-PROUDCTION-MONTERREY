<?php

namespace App\Http\Resources;

use App\Models\Certificado;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Certificado */
class CertificadoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'consecutivo' => $this->consecutivo,
            'codigo_verificacion' => $this->codigo_verificacion,
            'hash_documento' => $this->hash_documento,
            'estado' => $this->estado->value,
            'estado_label' => $this->estado->label(),
            'vigente' => $this->estaVigente(),
            'firmado_por' => $this->whenLoaded('firmadoPor', fn () => $this->firmadoPor?->name),
            'fecha_expedicion' => $this->fecha_expedicion,
            'vigencia_hasta' => $this->vigencia_hasta,
        ];
    }
}
