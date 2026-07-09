<?php

namespace App\Services;

use App\Enums\EstadoSolicitud;
use App\Enums\ResultadoValidacion;
use App\Models\Solicitud;
use App\Models\User;
use App\Models\Validacion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ValidacionService
{
    public function __construct(
        private readonly SolicitudService $solicitudes,
        private readonly AuditService $audit,
        private readonly DocumentoService $documentos,
    ) {}

    /** Tipos de soporte permitidos y su documento asociado. */
    private const TIPO_DOC = [
        'electoral' => 'soporte_electoral',
        'sisben' => 'soporte_sisben',
        'jac' => 'soporte_jac',
        'especial' => 'soporte_especial',
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
    ): Validacion {
        $validacion = DB::transaction(function () use ($solicitud, $tipo, $soporte, $meta, $resultado, $observacion, $actor) {
            $documentoId = null;

            if ($soporte) {
                $documentoId = $this->almacenarSoporte($solicitud, $tipo, $soporte, $actor);
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
    ): Solicitud {
        DB::transaction(function () use ($solicitud, $resultado, $observacion, $actor) {
            $solicitud->validaciones()->create([
                'tipo' => 'prevalidacion',
                'resultado' => $resultado,
                'observacion' => $observacion,
                'validado_por' => $actor->id,
                'validado_at' => now(),
            ]);

            $this->audit->registrar(
                accion: 'prevalidacion.concepto',
                auditable: $solicitud,
                descripcion: "Prevalidación: {$resultado->label()}",
                despues: ['resultado' => $resultado->value, 'observacion' => $observacion],
                actor: $actor,
            );
        });

        $nuevoEstado = match ($resultado) {
            ResultadoValidacion::Cumple => EstadoSolicitud::Preaprobada,
            ResultadoValidacion::Subsanar => EstadoSolicitud::PendienteSoporte,
            ResultadoValidacion::Rechaza => EstadoSolicitud::Rechazada,
        };

        return $this->solicitudes->cambiarEstado(
            $solicitud,
            $nuevoEstado,
            "Prevalidación: {$resultado->label()}".($observacion ? " — {$observacion}" : ''),
            $actor,
        );
    }

    /**
     * Subsanación por el ciudadano: re-carga soporte y/o actualiza la justificación
     * cuando la solicitud está en "Pendiente de soporte", devolviéndola a "En validación".
     */
    public function subsanar(
        Solicitud $solicitud,
        ?UploadedFile $soporte,
        ?string $justificacion,
        User $actor,
    ): Solicitud {
        if ($solicitud->estado !== EstadoSolicitud::PendienteSoporte) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'estado' => ['La solicitud no requiere subsanación en este momento.'],
            ]);
        }

        $tipo = $solicitud->medio_acreditacion->value;

        DB::transaction(function () use ($solicitud, $soporte, $justificacion, $tipo, $actor) {
            $documentoId = null;

            if ($soporte) {
                $documentoId = $this->almacenarSoporte($solicitud, $tipo, $soporte, $actor);
            }

            if ($justificacion !== null) {
                $solicitud->update(['justificacion_especial' => $justificacion]);
            }

            $solicitud->validaciones()->create([
                'tipo' => $tipo,
                'observacion' => 'Subsanación aportada por el ciudadano.',
                'documento_id' => $documentoId,
                'validado_por' => $actor->id,
                'validado_at' => now(),
            ]);

            $this->audit->registrar(
                accion: 'solicitud.subsanada',
                auditable: $solicitud,
                descripcion: 'El ciudadano aportó la subsanación solicitada.',
                actor: $actor,
            );
        });

        return $this->solicitudes->cambiarEstado(
            $solicitud,
            EstadoSolicitud::EnValidacion,
            'Subsanación recibida; regresa a validación.',
            $actor,
        );
    }

    /** Almacena el archivo en el expediente (versionado) y devuelve el id del documento. */
    private function almacenarSoporte(Solicitud $solicitud, string $tipo, UploadedFile $file, User $actor): int
    {
        $expediente = $solicitud->expediente()->firstOrFail();

        return $this->documentos
            ->guardarSubido($expediente, self::TIPO_DOC[$tipo] ?? 'otro', $file, $actor)
            ->id;
    }
}
