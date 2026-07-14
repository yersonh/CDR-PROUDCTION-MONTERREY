<?php

namespace App\Services;

use App\Enums\EstadoSolicitud;
use App\Enums\ResultadoValidacion;
use App\Jobs\ValidarCertificadoElectoralConIA;
use App\Models\Solicitud;
use App\Models\User;
use App\Models\Validacion;
use App\Notifications\ConceptoRegistradoNotification;
use App\Notifications\SolicitudRechazadaNotification;
use App\Notifications\SubsanacionRecibidaNotification;
use App\Support\TipoDocumentoCatalogo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ValidacionService
{
    public function __construct(
        private readonly SolicitudService $solicitudes,
        private readonly AuditService $audit,
        private readonly DocumentoService $documentos,
        private readonly NotificacionService $notificaciones,
    ) {}

    /**
     * Tipos de soporte permitidos y su documento asociado.
     *
     * OJO: sisben/jac usan un tipo distinto al que guarda el soporte
     * original del ciudadano (`soporte_sisben`/`soporte_jac`, ver
     * SolicitudService::almacenarSoporte). Son documentos distintos —
     * la Respuesta de Oficio / certificación del especialista no
     * reemplaza el soporte que trajo la solicitud, así que no pueden
     * compartir `tipo` o el versionado de DocumentoService los fusiona.
     */
    private const TIPO_DOC = [
        'electoral' => 'soporte_electoral',
        'sisben' => 'respuesta_oficio_sisben',
        'jac' => 'certificacion_jac',
    ];

    /**
     * Registra la validación/carga de un soporte y, si aplica, mueve la
     * solicitud a "En validación".
     *
     * @param  array<string, mixed>|null  $meta   Metadatos (p. ej. campos JAC)
     */
    public function registrarSoporte(
        Solicitud $solicitud,
        string $tipo,
        ?UploadedFile $soporte,
        ?array $meta,
        ?ResultadoValidacion $resultado,
        ?string $observacion,
        User $actor,
        bool $permiteRevalidar = false,
    ): Validacion {
        // Cada Presidente JAC solo puede certificar solicitudes de su propio
        // sector (login individual por sector, ver PresidenteJac) — a menos
        // que tenga solicitudes.ver_todas (Super Admin operando en su nombre).
        if ($tipo === 'jac' && ! $actor->can('solicitudes.ver_todas')) {
            $sectorActor = $actor->presidenteJac()->where('estado', 'activo')->value('sector_id');

            if ($sectorActor === null || $sectorActor !== $solicitud->sector_id) {
                throw ValidationException::withMessages([
                    'tipo' => 'No tiene autorización para certificar solicitudes de este sector.',
                ]);
            }
        }

        // SISBEN, JAC y electoral son un concepto único de quien valida de
        // fondo (especialista o, en electoral, la IA): una sola validación
        // por solicitud, no una por cada intento. Si Secretaría no está de
        // acuerdo con lo que registró la IA, su mecanismo de corrección es
        // la prevalidación ("Emitir concepto"), no volver a enviar este
        // formulario — por eso ya no se permite re-enviar "electoral" aquí.
        // Única excepción: ValidarCertificadoElectoralConIA re-evaluando tras
        // una subsanación (el ciudadano cargó un certificado electoral nuevo),
        // que pasa $permiteRevalidar = true a propósito.
        if (in_array($tipo, ['sisben', 'jac', 'electoral'], true) && ! $permiteRevalidar && $solicitud->validaciones()->where('tipo', $tipo)->exists()) {
            throw ValidationException::withMessages([
                'tipo' => "Ya se registró la validación de {$tipo} para esta solicitud.",
            ]);
        }

        $validacion = DB::transaction(function () use ($solicitud, $tipo, $soporte, $meta, $resultado, $observacion, $actor) {
            $documentoId = null;

            if ($soporte) {
                $documentoId = $this->almacenarSoporte($solicitud, self::TIPO_DOC[$tipo] ?? 'otro', $soporte, $actor);
            }

            $validacion = $solicitud->validaciones()->create([
                'tipo' => $tipo,
                'resultado' => $resultado,
                'observacion' => $observacion,
                'meta' => $meta,
                'documento_id' => $documentoId,
                'validado_por' => $actor->id,
                'validado_at' => now(),
            ]);

            $this->audit->registrar(
                accion: 'validacion.registrada',
                auditable: $solicitud,
                descripcion: "Soporte {$tipo} registrado".($resultado ? " ({$resultado->value})" : ''),
                despues: ['tipo' => $tipo, 'resultado' => $resultado?->value, 'meta' => $meta],
                actor: $actor,
            );

            return $validacion;
        });

        // Al recibir el primer soporte, pasa a "En validación"
        if (in_array($solicitud->estado, [EstadoSolicitud::Radicada, EstadoSolicitud::PendienteSoporte], true)) {
            $this->solicitudes->cambiarEstado(
                $solicitud,
                EstadoSolicitud::EnValidacion,
                "Soporte {$tipo} recibido; inicia validación.",
                $actor,
            );
        }

        // El especialista (SISBEN/JAC) o la IA (electoral) ya hicieron su
        // parte — avisar a Secretaría que la solicitud está lista para
        // prevalidación, con quién actuó y qué resultado dio (no un aviso
        // genérico). Si es la propia Secretaría quien validó electoral a
        // mano, no tiene sentido notificarse a sí misma.
        if (in_array($tipo, ['sisben', 'jac', 'electoral'], true) && ! $actor->hasRole('secretaria')) {
            try {
                $quien = match ($tipo) {
                    'sisben' => 'El Funcionario SISBEN',
                    'jac' => 'El Presidente JAC',
                    'electoral' => 'La validación automática (IA)',
                    default => 'El especialista',
                };
                $accion = $resultado === ResultadoValidacion::Rechaza ? 'rechazó' : 'aprobó';

                // Tras una subsanación, dejar claro que es una segunda
                // evaluación del mismo certificado electoral (ya corregido),
                // no confundirla con el primer intento.
                $mensaje = $permiteRevalidar
                    ? "Tras la subsanación, la IA volvió a evaluar el certificado electoral de {$solicitud->nombre_completo} y lo {$accion} — lista para prevalidación."
                    : "{$quien} {$accion} la solicitud {$solicitud->radicado} — lista para prevalidación.";

                $this->notificaciones->notificarRoles(['secretaria'], $mensaje, $solicitud);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        // SISBEN y JAC son el concepto del especialista sobre la solicitud
        // (positivo o negativo) — se le avisa al ciudadano en cuanto queda
        // registrado, sin esperar a la prevalidación de Secretaría.
        if (in_array($tipo, ['sisben', 'jac'], true) && $resultado !== null) {
            $this->notificarConcepto($solicitud, $tipo, $resultado, $observacion);
        }

        return $validacion->load('documento', 'validadoPor');
    }

    /**
     * Emite el concepto de prevalidación y transiciona la solicitud.
     */
    public function prevalidar(
        Solicitud $solicitud,
        ResultadoValidacion $resultado,
        ?string $observacion,
        User $actor,
        ?string $tipoDocumento = null,
    ): Solicitud {
        // Quien prevalida "cumple" es quien firma como "Proyectó" en el
        // certificado final (ver CertificadoService::renderPdf) — no debe
        // poder enviarla al Alcalde sin haber cargado antes su propia firma.
        if ($resultado === ResultadoValidacion::Cumple
            && (! $actor->firma_path || ! Storage::disk('local')->exists($actor->firma_path))
        ) {
            throw ValidationException::withMessages([
                'resultado' => 'No tiene firma electrónica registrada. Debe cargarla en Mi perfil antes de prevalidar una solicitud.',
            ]);
        }

        DB::transaction(function () use ($solicitud, $resultado, $observacion, $actor, $tipoDocumento) {
            $solicitud->validaciones()->create([
                'tipo' => 'prevalidacion',
                'resultado' => $resultado,
                'observacion' => $observacion,
                'meta' => $tipoDocumento ? [
                    'tipo_documento_solicitado' => $tipoDocumento,
                    'tipo_documento_solicitado_label' => TipoDocumentoCatalogo::label($tipoDocumento),
                ] : null,
                'validado_por' => $actor->id,
                'validado_at' => now(),
            ]);

            $this->audit->registrar(
                accion: 'prevalidacion.concepto',
                auditable: $solicitud,
                descripcion: "Prevalidación: {$resultado->label()}",
                despues: ['resultado' => $resultado->value, 'observacion' => $observacion, 'tipo_documento' => $tipoDocumento],
                actor: $actor,
            );
        });

        $nuevoEstado = match ($resultado) {
            ResultadoValidacion::Cumple => EstadoSolicitud::Preaprobada,
            ResultadoValidacion::Subsanar => EstadoSolicitud::PendienteSoporte,
            ResultadoValidacion::Rechaza => EstadoSolicitud::Rechazada,
        };

        $solicitud = $this->solicitudes->cambiarEstado(
            $solicitud,
            $nuevoEstado,
            "Prevalidación: {$resultado->label()}".($observacion ? " — {$observacion}" : ''),
            $actor,
        );

        $this->notificarConcepto($solicitud, 'secretaria', $resultado, $observacion, $tipoDocumento);

        // Al Alcalde solo le avisamos cuando se rechaza — es informativo /
        // de supervisión, no tiene que actuar (la solicitud queda en estado
        // terminal). Si aprueba ("cumple"), el propio flujo ya lo lleva a su
        // bandeja de firma, no hace falta un aviso aparte.
        if ($resultado === ResultadoValidacion::Rechaza) {
            $this->notificarRechazoAlAlcalde($solicitud, $observacion);
        }

        return $solicitud;
    }

    /**
     * Subsanación por el ciudadano: re-carga soporte y/o actualiza la justificación
     * cuando la solicitud está en "Pendiente de soporte", devolviéndola a "En validación".
     *
     * $actor es null cuando llega por el enlace público firmado que se envía
     * por correo (el ciudadano no tiene cuenta en el sistema) — en ese caso
     * la autorización ya la dio la firma de la URL, no un usuario autenticado.
     */
    public function subsanar(
        Solicitud $solicitud,
        ?UploadedFile $soporte,
        ?User $actor,
    ): Solicitud {
        if ($solicitud->estado !== EstadoSolicitud::PendienteSoporte) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'estado' => ['La solicitud no requiere subsanación en este momento.'],
            ]);
        }

        $tipo = $solicitud->medio_acreditacion->value;

        // El documento a corregir es el que Secretaría eligió al pedir la
        // subsanación (PrevalidacionRequest::tipo_documento, guardado en el
        // meta de la última validación "prevalidacion"). Solicitudes
        // antiguas sin ese dato caen al soporte original del medio, que era
        // el único documento que se podía pedir antes de este cambio.
        $ultimaPrevalidacion = $solicitud->validaciones()
            ->where('tipo', 'prevalidacion')
            ->latest('validado_at')
            ->first();
        $tipoDocumento = $ultimaPrevalidacion?->meta['tipo_documento_solicitado'] ?? 'soporte_'.$tipo;

        DB::transaction(function () use ($solicitud, $soporte, $tipo, $tipoDocumento, $actor) {
            $documentoId = null;

            if ($soporte) {
                // Se versiona el documento exacto que se pidió corregir, no
                // siempre el soporte original — puede ser el documento de
                // identidad o la solicitud firmada, según lo que Secretaría
                // haya seleccionado en la prevalidación.
                $documentoId = $this->almacenarSoporte($solicitud, $tipoDocumento, $soporte, $actor);
            }

            $solicitud->validaciones()->create([
                'tipo' => $tipo,
                'observacion' => 'Subsanación aportada por el ciudadano.',
                'documento_id' => $documentoId,
                'validado_por' => $actor?->id,
                'validado_at' => now(),
            ]);

            $this->audit->registrar(
                accion: 'solicitud.subsanada',
                auditable: $solicitud,
                descripcion: $actor
                    ? 'El ciudadano aportó la subsanación solicitada.'
                    : 'El ciudadano aportó la subsanación solicitada, vía enlace público.',
                actor: $actor,
            );
        });

        $solicitud = $this->solicitudes->cambiarEstado(
            $solicitud,
            EstadoSolicitud::EnValidacion,
            'Subsanación recibida; regresa a validación.',
            $actor,
        );

        // Aviso a Secretaría (correo + campanita) de que el ciudadano ya
        // respondió y volvió a subir lo pedido — tanto si entró por el
        // enlace público (sin cuenta) como si lo hizo autenticado desde su
        // portal; si un funcionario la registró manualmente desde el panel
        // interno, no hace falta avisarse a sí mismo.
        if ($actor === null || $actor->hasRole('ciudadano')) {
            $this->notificarSubsanacionRecibida($solicitud, $tipoDocumento);
        }

        // El certificado electoral es el único documento que un sistema (no
        // una persona) evalúa de fondo — si es lo que se corrigió, la IA
        // debe volver a revisarlo, igual que lo hizo la primera vez.
        if ($tipoDocumento === 'soporte_electoral') {
            ValidarCertificadoElectoralConIA::dispatch($solicitud->id);
        }

        return $solicitud;
    }

    /**
     * Avisa al ciudadano por correo el concepto registrado (SISBEN, JAC o
     * Secretaría), positivo o negativo. No debe bloquear el flujo si falla.
     */
    private function notificarConcepto(Solicitud $solicitud, string $origen, ResultadoValidacion $resultado, ?string $observacion, ?string $tipoDocumento = null): void
    {
        try {
            Notification::route('mail', $solicitud->correo)
                ->notify(new ConceptoRegistradoNotification($solicitud, $origen, $resultado, $observacion, $tipoDocumento));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Avisa al Alcalde (correo + campanita) que una solicitud fue rechazada
     * en la prevalidación. No debe bloquear el flujo si falla.
     */
    private function notificarRechazoAlAlcalde(Solicitud $solicitud, ?string $observacion): void
    {
        try {
            $mensaje = "La solicitud {$solicitud->radicado} de {$solicitud->nombre_completo} fue rechazada en la prevalidación.";

            $this->notificaciones->notificarRoles(['alcalde'], $mensaje, $solicitud);

            $destinatarios = User::role('alcalde')->get();

            if ($destinatarios->isNotEmpty()) {
                Notification::send($destinatarios, new SolicitudRechazadaNotification($solicitud, $observacion));
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * El ciudadano ya corrigió y volvió a enviar lo pedido — Secretaría debe
     * volver a revisarlo (correo + campanita). No debe bloquear el flujo si falla.
     */
    private function notificarSubsanacionRecibida(Solicitud $solicitud, string $tipoDocumento): void
    {
        try {
            $documentoLabel = TipoDocumentoCatalogo::label($tipoDocumento);
            $mensaje = "El ciudadano respondió la subsanación de la solicitud {$solicitud->radicado} y cargó: {$documentoLabel}.";

            $this->notificaciones->notificarRoles(['secretaria'], $mensaje, $solicitud);

            $destinatarios = User::role('secretaria')->get();

            if ($destinatarios->isNotEmpty()) {
                Notification::send($destinatarios, new SubsanacionRecibidaNotification($solicitud, $documentoLabel));
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Almacena el archivo en el expediente (versionado) y devuelve el id del
     * documento. Recibe el `tipo` de documento ya resuelto por el llamador
     * (no aplica TIPO_DOC aquí) — cada caller decide si versiona el soporte
     * original del ciudadano o guarda la respuesta del especialista.
     */
    private function almacenarSoporte(Solicitud $solicitud, string $tipoDocumento, UploadedFile $file, ?User $actor): int
    {
        $expediente = $solicitud->expediente()->firstOrFail();

        return $this->documentos
            ->guardarSubido($expediente, $tipoDocumento, $file, $actor)
            ->id;
    }
}
