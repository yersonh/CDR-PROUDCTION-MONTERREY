<?php

namespace App\Jobs;

use App\Models\SolicitudPublica;
use App\Services\ClienteVur;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class EnviarSolicitudPublicaAVur implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** Backoff creciente para no saturar VUR si está caído. */
    public array $backoff = [30, 60, 300, 900];

    public function __construct(private readonly int $solicitudPublicaId) {}

    public function handle(ClienteVur $vur): void
    {
        $solicitud = SolicitudPublica::find($this->solicitudPublicaId);

        if (! $solicitud || $solicitud->estado === 'enviado') {
            return;
        }

        // A VUR se le envía el documento FIRMADO (impreso, firmado a mano y
        // vuelto a subir por el ciudadano) como pdf_solicitud — no hay firma
        // electrónica en este canal. El borrador autogenerado (ruta_pdf) se
        // queda solo como registro interno de CDR.
        $pdfPath = Storage::disk('local')->path($solicitud->ruta_pdf_firmado);
        $soportePath = $solicitud->ruta_soporte
            ? Storage::disk('local')->path($solicitud->ruta_soporte)
            : null;
        $documentoIdentidadPath = $solicitud->ruta_documento_identidad
            ? Storage::disk('local')->path($solicitud->ruta_documento_identidad)
            : null;

        $resultado = $vur->enviarSolicitud(
            datos: [
                'nombre_completo' => $solicitud->nombre_completo,
                'tipo_documento' => $solicitud->tipo_documento,
                'numero_identificacion' => $solicitud->numero_identificacion,
                'direccion' => $solicitud->direccion,
                'correo' => $solicitud->correo,
                'celular' => $solicitud->celular,
                'barrio_vereda_sector' => $solicitud->barrio_vereda_sector,
                'motivo' => $solicitud->motivo,
                'tipo_certificado' => $solicitud->tipo_certificado->value,
                'medio_acreditacion' => $solicitud->medio_acreditacion->value,
                'referencia_cdr' => $solicitud->id,
                // Código de seguimiento que el ciudadano usa en "Consultar
                // solicitud" (mismo formato que SolicitudPublicaController::
                // store/consultar) — VUR lo puede incluir en sus propias
                // comunicaciones/respuestas al ciudadano.
                'codigo_seguimiento_cdr' => 'SP-'.Str::padLeft((string) $solicitud->id, 8, '0'),
            ],
            pdfPath: $pdfPath,
            pdfNombre: "solicitud_firmada_{$solicitud->id}.pdf",
            soportePath: $soportePath,
            documentoIdentidadPath: $documentoIdentidadPath,
        );

        if ($resultado['ok']) {
            $solicitud->update([
                'estado' => 'enviado',
                'radicado_vur' => $resultado['radicado_vur'],
                'enviado_at' => now(),
                'intentos' => $solicitud->intentos + 1,
                'ultimo_error' => null,
            ]);

            return;
        }

        $solicitud->update([
            'estado' => 'error',
            'ultimo_error' => "HTTP {$resultado['status']}: ".mb_substr((string) $resultado['body'], 0, 500),
            'intentos' => $solicitud->intentos + 1,
        ]);

        // Lanza para que la cola reintente según $tries/$backoff; si se agotan
        // los intentos, Laravel invoca failed() y el estado queda en "error".
        throw new RuntimeException("Error al enviar solicitud pública #{$solicitud->id} a VUR: HTTP {$resultado['status']}");
    }

    public function failed(?Throwable $exception): void
    {
        SolicitudPublica::whereKey($this->solicitudPublicaId)->update([
            'estado' => 'error',
            'ultimo_error' => $exception?->getMessage(),
        ]);
    }
}
