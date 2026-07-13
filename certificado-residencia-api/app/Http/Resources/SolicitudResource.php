<?php

namespace App\Http\Resources;

use App\Models\Solicitud;
use App\Services\ClienteCore;
use App\Support\SlaCalculator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Solicitud */
class SolicitudResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'radicado' => $this->radicado,
            'tipo_certificado' => [
                'value' => $this->tipo_certificado->value,
                'label' => $this->tipo_certificado->label(),
            ],
            'medio_acreditacion' => [
                'value' => $this->medio_acreditacion->value,
                'label' => $this->medio_acreditacion->label(),
            ],
            'estado' => [
                'value' => $this->estado->value,
                'label' => $this->estado->label(),
                'color' => $this->estado->color(),
            ],
            'ciudadano' => [
                'nombre_completo' => $this->nombre_completo,
                'tipo_documento' => $this->tipo_documento,
                'numero_identificacion' => $this->numero_identificacion,
                'direccion' => $this->direccion,
                'correo' => $this->correo,
                'celular' => $this->celular,
                'barrio_vereda_sector' => $this->barrio_vereda_sector,
            ],
            'sector' => $this->sector_id ? ['id' => $this->sector_id, 'nombre' => $this->sector?->nombre] : null,
            'motivo' => $this->motivo,
            'justificacion_especial' => $this->justificacion_especial,
            'fecha_radicacion' => $this->fecha_radicacion,
            'sla' => [
                'fecha_limite' => $this->fecha_limite_sla,
                'dias_habiles_restantes' => $this->fecha_limite_sla
                    ? SlaCalculator::diasHabilesRestantes($this->fecha_limite_sla)
                    : null,
                'semaforo' => $this->semaforoSla(),
            ],
            'dependencia' => $this->when($this->dependencia_id !== null, function () {
                try {
                    $dependencia = app(ClienteCore::class)->dependencia($this->dependencia_id);
                } catch (\Throwable) {
                    return null;
                }

                return $dependencia['nombre'] ?? null;
            }),
            'expediente' => new ExpedienteResource($this->whenLoaded('expediente')),
            'certificado' => new CertificadoResource($this->whenLoaded('certificado')),
            'validaciones' => ValidacionResource::collection($this->whenLoaded('validaciones')),
            'seguimientos' => SeguimientoResource::collection($this->whenLoaded('seguimientos')),
            'creado_at' => $this->created_at,
        ];
    }
}
