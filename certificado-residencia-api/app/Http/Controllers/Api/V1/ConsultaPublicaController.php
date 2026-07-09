<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Certificado;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Consulta pública de autenticidad (sin autenticación) — paso 9 del flujo.
 */
class ConsultaPublicaController extends Controller
{
    /**
     * Verifica un certificado por su código de verificación (QR o manual).
     */
    public function verificar(string $codigo): JsonResponse
    {
        $certificado = Certificado::with('solicitud', 'firmadoPor')
            ->where('codigo_verificacion', strtoupper($codigo))
            ->first();

        if (! $certificado) {
            return response()->json([
                'valido' => false,
                'message' => 'No se encontró un certificado con ese código de verificación.',
            ], Response::HTTP_NOT_FOUND);
        }

        $s = $certificado->solicitud;

        return response()->json([
            'valido' => true,
            'vigente' => $certificado->estaVigente(),
            'certificado' => [
                'consecutivo' => $certificado->consecutivo,
                'codigo_verificacion' => $certificado->codigo_verificacion,
                'estado' => $certificado->estado->label(),
                'tipo' => $s->tipo_certificado->label(),
                'radicado' => $s->radicado,
                'ciudadano' => $s->nombre_completo,
                'identificacion' => $s->numero_identificacion,
                'autoridad' => 'Alcaldía Municipal de Monterrey, Casanare',
                'firmado_por' => $certificado->firmadoPor?->name,
                'fecha_expedicion' => $certificado->fecha_expedicion,
                'vigencia_hasta' => $certificado->vigencia_hasta,
                'hash_documento' => $certificado->hash_documento,
            ],
        ]);
    }

    /**
     * Descarga pública del PDF por código de verificación.
     */
    public function descargar(string $codigo): StreamedResponse
    {
        $certificado = Certificado::where('codigo_verificacion', strtoupper($codigo))->firstOrFail();

        abort_unless(
            $certificado->pdf_path && Storage::disk('local')->exists($certificado->pdf_path),
            Response::HTTP_NOT_FOUND,
        );

        return Storage::disk('local')->download(
            $certificado->pdf_path,
            "Certificado_{$certificado->consecutivo}.pdf",
        );
    }
}
