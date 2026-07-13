<?php

namespace App\Services;

use App\Jobs\EnviarSolicitudPublicaAVur;
use App\Models\SolicitudPublica;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Captación pública (sin login): guarda la solicitud del ciudadano, genera
 * el PDF estándar de solicitud y encola el envío a VUR. No crea una
 * Solicitud/Expediente en CDR — eso ocurre cuando VUR reenvía la solicitud
 * ya radicada por "recibidos_vur" y secretaría la formaliza.
 */
class SolicitudPublicaService
{
    private const MESES = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio',
        7 => 'julio', 8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
    ];

    /**
     * @param  array<string, mixed>  $datos
     */
    public function crear(
        array $datos,
        ?UploadedFile $soporte,
        UploadedFile $documentoIdentidad,
        UploadedFile $documentoFirmado,
    ): SolicitudPublica {
        $solicitudPublica = DB::transaction(function () use ($datos, $soporte, $documentoIdentidad, $documentoFirmado) {
            $solicitudPublica = SolicitudPublica::create([
                ...$datos,
                'ruta_pdf' => '',
                'estado' => 'pendiente',
            ]);

            if ($soporte) {
                $rutaSoporte = $soporte->store("solicitudes-publicas/{$solicitudPublica->id}", 'local');
                $solicitudPublica->update(['ruta_soporte' => $rutaSoporte]);
            }

            $rutaDocumentoIdentidad = $documentoIdentidad->store("solicitudes-publicas/{$solicitudPublica->id}", 'local');
            $rutaPdfFirmado = $documentoFirmado->store("solicitudes-publicas/{$solicitudPublica->id}", 'local');
            $solicitudPublica->update([
                'ruta_documento_identidad' => $rutaDocumentoIdentidad,
                'ruta_pdf_firmado' => $rutaPdfFirmado,
            ]);

            // ruta_pdf es el borrador autogenerado — se conserva como
            // registro interno de CDR, pero no es lo que se envía a VUR (eso
            // es ruta_pdf_firmado, ver EnviarSolicitudPublicaAVur).
            $pdf = $this->renderPdf($solicitudPublica);
            $rutaPdf = "solicitudes-publicas/{$solicitudPublica->id}/solicitud_{$solicitudPublica->id}.pdf";
            Storage::disk('local')->put($rutaPdf, $pdf);
            $solicitudPublica->update(['ruta_pdf' => $rutaPdf]);

            return $solicitudPublica;
        });

        EnviarSolicitudPublicaAVur::dispatch($solicitudPublica->id);

        return $solicitudPublica->refresh();
    }

    public function renderPdf(SolicitudPublica $s): string
    {
        return $this->render($s, 'SP-'.Str::padLeft((string) $s->id, 8, '0'));
    }

    /**
     * Genera el PDF a partir de los datos del formulario sin persistir nada,
     * para que el ciudadano lo revise antes de enviar (paso de confirmación).
     *
     * @param  array<string, mixed>  $datos
     */
    public function renderPreview(array $datos): string
    {
        return $this->render(new SolicitudPublica($datos), 'VISTA PREVIA — sin radicar');
    }

    private function render(SolicitudPublica $s, string $referencia): string
    {
        $ahora = now();

        return Pdf::loadView('solicitudes.solicitud_publica', [
            's' => $s,
            'referencia' => $referencia,
            'fecha' => $ahora->format('d/m/Y H:i'),
            'fechaLarga' => $ahora->format('d').' de '.self::MESES[(int) $ahora->format('n')].' de '.$ahora->format('Y'),
        ])->setPaper('letter')->output();
    }
}
