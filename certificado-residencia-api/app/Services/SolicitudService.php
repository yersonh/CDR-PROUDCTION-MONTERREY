<?php

namespace App\Services;

use App\DTOs\CreateSolicitudData;
use App\Enums\EstadoSolicitud;
use App\Enums\MedioAcreditacion;
use App\Jobs\NotificarEstadoRecibidoAVur;
use App\Models\Expediente;
use App\Models\PresidenteJac;
use App\Models\RecibidoVur;
use App\Models\Solicitud;
use App\Models\User;
use App\Support\SlaCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SolicitudService
{
    public function __construct(
        private readonly RadicadoGenerator $radicados,
        private readonly AuditService $audit,
        private readonly DocumentoService $documentos,
        private readonly NotificacionService $notificaciones,
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

        $recibido = null;

        DB::transaction(function () use ($solicitud, $anterior, $nuevo, $nota, $actor, &$recibido) {
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

            if ($nuevo->esTerminal()) {
                // Llegó a estado terminal — el recibido pasa de en_tramite a
                // procesado (ciclo pendiente → en_tramite → procesado).
                $recibido = RecibidoVur::where('solicitud_id', $solicitud->id)->first();
                $recibido?->update(['estado' => 'procesado']);
            } elseif ($anterior === EstadoSolicitud::Radicada && $nuevo === EstadoSolicitud::EnValidacion) {
                // Primera acción real sobre el trámite (alguien registró el
                // primer soporte) — este es el momento en que le avisamos a
                // VUR que pasó a "en trámite", NO cuando CDR crea la
                // solicitud automáticamente al recibir el radicado. Avisar
                // en ese momento sería falso: diría "en trámite" sin que
                // nadie hubiera hecho nada todavía.
                $recibido = RecibidoVur::where('solicitud_id', $solicitud->id)->first();
            }
        });

        if ($recibido && $recibido->radicado_vur) {
            $estadoVur = match (true) {
                $nuevo === EstadoSolicitud::Certificada => 'RESPONDIDO',
                $nuevo->esTerminal() => 'CERRADO',
                default => 'EN_TRAMITE',
            };

            // Al responder, VUR necesita el PDF del certificado firmado, no
            // solo el estado — es la respuesta a la entrada que él mismo
            // radicó (ver ClienteVur::notificarEstado).
            $documentoRespuestaPath = $estadoVur === 'RESPONDIDO'
                ? $solicitud->certificado?->pdf_path
                : null;
            $documentoRespuestaPath = $documentoRespuestaPath
                ? Storage::disk('local')->path($documentoRespuestaPath)
                : null;

            NotificarEstadoRecibidoAVur::dispatch($recibido->radicado_vur, $estadoVur, $documentoRespuestaPath);
        }

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
                'radicado_vur' => $data->radicadoVur,
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
                'sector_id' => $data->sectorId,
                'motivo' => $data->motivo,
                'estado' => EstadoSolicitud::Radicada,
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

            // Vincula el recibido de VUR de origen, si aplica (bandeja de
            // entrada externa). "en_tramite" (no "procesado" todavía) — el
            // recibido solo pasa a procesado cuando la solicitud llegue a un
            // estado terminal, ver cambiarEstado().
            if ($data->recibidoVurId) {
                RecibidoVur::whereKey($data->recibidoVurId)->update([
                    'estado' => 'en_tramite',
                    'solicitud_id' => $solicitud->id,
                    'procesado_at' => $ahora,
                ]);
            }

            return $solicitud;
        });

        // Aviso interno a quien debe gestionar el trámite (campanita), tanto
        // si la solicitud vino del formulario público como del auto-enrutamiento de VUR.
        $this->notificarNuevaSolicitud($solicitud);

        // OJO: no se avisa "EN_TRAMITE" a VUR aquí — el recibido queda
        // en_tramite del lado de CDR (arriba), pero avisarle a VUR en este
        // punto sería prematuro (nadie ha hecho nada todavía). Ese aviso
        // sale en cambiarEstado() cuando ocurre la primera acción real.
        return $solicitud->load(['expediente.documentos', 'seguimientos']);
    }

    private function almacenarSoporte(Expediente $expediente, CreateSolicitudData $data): void
    {
        $tipo = 'soporte_'.$data->medioAcreditacion->value;
        $actor = $data->createdBy ? User::find($data->createdBy) : null;

        $this->documentos->guardarSubido($expediente, $tipo, $data->soporte, $actor ?? User::findOrFail($data->ciudadanoId));
    }

    /**
     * SISBEN y JAC van directo al especialista de su medio; electoral lo
     * maneja Secretaría desde "radicada". No debe bloquear la radicación si
     * falla — es solo un aviso interno.
     */
    private function notificarNuevaSolicitud(Solicitud $solicitud): void
    {
        try {
            $mensaje = "Nueva solicitud {$solicitud->radicado} de {$solicitud->nombre_completo} requiere su gestión.";

            // JAC ya no es un rol genérico: cada presidente solo ve su
            // propio sector (ver PresidenteJac), así que se notifica
            // puntualmente al presidente vigente de ese sector, no a todos
            // los que tengan el rol. Si el sector no tiene presidente
            // asignado todavía, se avisa a Secretaría para que lo gestione.
            if ($solicitud->medio_acreditacion === MedioAcreditacion::Jac) {
                $userId = PresidenteJac::where('sector_id', $solicitud->sector_id)
                    ->where('estado', 'activo')
                    ->value('user_id');

                if ($userId) {
                    $this->notificaciones->notificarUsuarios([$userId], $mensaje, $solicitud);
                } else {
                    $this->notificaciones->notificarRoles(['secretaria'], $mensaje, $solicitud);
                }

                return;
            }

            $roles = match ($solicitud->medio_acreditacion) {
                MedioAcreditacion::Sisben => ['funcionario_sisben'],
                default => ['secretaria'],
            };

            $this->notificaciones->notificarRoles($roles, $mensaje, $solicitud);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
