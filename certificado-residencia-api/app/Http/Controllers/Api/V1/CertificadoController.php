<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Certificado\FirmarRequest;
use App\Models\Solicitud;
use App\Services\CertificadoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CertificadoController extends Controller
{
    public function __construct(private readonly CertificadoService $certificados) {}

    /**
     * Firma (individual o masiva) de solicitudes preaprobadas.
     */
    public function firmar(FirmarRequest $request): JsonResponse
    {
        $solicitudes = Solicitud::whereIn('id', $request->validated('solicitud_ids'))
            ->with('expediente')
            ->get();

        $resultado = $this->certificados->firmarLote($solicitudes, $request->user());

        $n = count($resultado['firmadas']);

        return response()->json([
            'message' => $n > 0
                ? "{$n} certificado(s) firmado(s) y expedido(s)."
                : 'No se firmó ningún certificado.',
            ...$resultado,
        ]);
    }

    /**
     * Descarga del PDF del certificado (autenticado).
     */
    public function descargar(Request $request, Solicitud $solicitud): StreamedResponse
    {
        $user = $request->user();
        $esPropia = $solicitud->ciudadano_id === $user->id;

        abort_unless(
            $user->can('certificados.ver') || ($user->can('solicitudes.ver_propias') && $esPropia),
            Response::HTTP_FORBIDDEN,
        );

        $certificado = $solicitud->certificado()->firstOrFail();
        abort_unless($certificado->pdf_path && Storage::disk('local')->exists($certificado->pdf_path), Response::HTTP_NOT_FOUND);

        return Storage::disk('local')->download(
            $certificado->pdf_path,
            "Certificado_{$certificado->consecutivo}.pdf",
        );
    }
}
