<?php

namespace App\Services;

use App\DTOs\CreateSolicitudData;
use App\Enums\EstadoSolicitud;
use App\Enums\MedioAcreditacion;
use App\Models\Expediente;
use App\Models\Solicitud;
use App\Notifications\SolicitudRadicadaNotification;
use App\Models\User;
use App\Support\SlaCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class SolicitudService
{
    public function __construct(
        private readonly RadicadoGenerator $radicados,
        private readonly AuditService $audit,
        private readonly DocumentoService $documentos,
    ) {}

    /**
     * Cambia el estado de una solicitud registrando trazabilidad y auditoría.
     */
    public function cambiarEstado(
        Solicitud $solicitud,
        EstadoSolicitud $nuevo,
        ?string $nota = null,
        ?User $actor = null,
    ): Solicitud {
        $anterior = $solicitud->estado;

        if ($anterior === $nuevo) {
            return $solicitud;
        }

        DB::transaction(function () use ($solicitud, $anterior, $nuevo, $nota, $actor) {
            $solicitud->update(['estado' => $nuevo]);

            $solicitud->seguimientos()->create([
                'estado_anterior' => $anterior,
                'estado_nuevo' => $nuevo,
                'nota' => $nota,
                'actor_id' => $actor?->id,
            ]);

            $this->audit->registrar(
                accion: 'solicitud.cambio_estado',
                auditable: $solicitud,
                descripcion: "Estado {$anterior->value} → {$nuevo->value}",
                antes: ['estado' => $anterior->value],
                despues: ['estado' => $nuevo->value],
                actor: $actor,
            );
        });

        return $solicitud->refresh();
    }

    /**
     * Radica una nueva solicitud: genera radicado, crea el expediente
     * electrónico, almacena el soporte, registra la trazabilidad inicial
     * y notifica al ciudadano.
     */
    public function radicar(CreateSolicitudData $data): Solicitud
    {
        $solicitud = DB::transaction(function () use ($data) {
            $ahora = now();

            $solicitud = Solicitud::create([
                'radicado' => $this->radicados->nuevoRadicado((int) $ahora->year),
                'ciudadano_id' => $data->ciudadanoId,
                'tipo_certificado' => $data->tipoCertificado,
                'medio_acreditacion' => $data->medioAcreditacion,
                'nombre_completo' => $data->nombreCompleto,
                'tipo_documento' => $data->tipoDocumento,
                'numero_identificacion' => $data->numeroIdentificacion,
                'direccion' => $data->direccion,
                'correo' => $data->correo,
                'celular' => $data->celular,
                'barrio_vereda_sector' => $data->barrioVeredaSector,
                'motivo' => $data->motivo,
                'estado' => EstadoSolicitud::Radicada,
                'justificacion_especial' => $data->justificacionEspecial,
                'fecha_radicacion' => $ahora,
                'fecha_limite_sla' => SlaCalculator::fechaLimiteTramite($ahora),
                'created_by' => $data->createdBy,
            ]);

            $expediente = Expediente::create([
                'solicitud_id' => $solicitud->id,
                'codigo' => $this->radicados->nuevoExpediente((int) $ahora->year),
            ]);

            // Soporte del ciudadano (obligatorio solo para Certificado Electoral)
            if ($data->soporte) {
                $this->almacenarSoporte($expediente, $data);
            }

            // Trazabilidad inicial del trámite
            $solicitud->seguimientos()->create([
                'estado_anterior' => null,
                'estado_nuevo' => EstadoSolicitud::Radicada,
                'nota' => 'Solicitud radicada automáticamente por el sistema.',
                'actor_id' => $data->createdBy,
            ]);

            return $solicitud;
        });

        // Notificación al ciudadano (fuera de la transacción)
        $this->notificarRadicacion($solicitud);

        return $solicitud->load(['expediente.documentos', 'seguimientos']);
    }

    private function almacenarSoporte(Expediente $expediente, CreateSolicitudData $data): void
    {
        $tipo = 'soporte_'.$data->medioAcreditacion->value;
        $actor = $data->createdBy ? User::find($data->createdBy) : null;

        $this->documentos->guardarSubido($expediente, $tipo, $data->soporte, $actor ?? User::findOrFail($data->ciudadanoId));
    }

    private function notificarRadicacion(Solicitud $solicitud): void
    {
        try {
            Notification::route('mail', $solicitud->correo)
                ->notify(new SolicitudRadicadaNotification($solicitud));
        } catch (\Throwable $e) {
            report($e); // No bloquea la radicación si el correo falla
        }
    }
}
