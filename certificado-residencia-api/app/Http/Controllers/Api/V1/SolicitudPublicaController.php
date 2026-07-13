<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Solicitud\PreviewSolicitudPublicaRequest;
use App\Http\Requests\Solicitud\StoreSolicitudPublicaRequest;
use App\Models\Sector;
use App\Services\SolicitudPublicaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SolicitudPublicaController extends Controller
{
    public function __construct(private readonly SolicitudPublicaService $solicitudesPublicas) {}

    /**
     * Puerta de entrada pública (sin login): un ciudadano diligencia el
     * formulario, se genera un PDF estándar de solicitud y se encola su
     * envío a VUR para que allá se radique.
     */
    public function store(StoreSolicitudPublicaRequest $request): JsonResponse
    {
        $datos = collect($request->validated())
            ->except(['sitio_web', 'soporte', 'documento_identidad', 'documento_firmado'])
            ->all();

        // barrio_vereda_sector se conserva como copia legible del sector
        // elegido (PDF, reportes, pantallas existentes ya lo consumen como
        // texto); sector_id es el vínculo estructurado nuevo.
        $datos['barrio_vereda_sector'] = Sector::findOrFail($datos['sector_id'])->nombre;

        $solicitud = $this->solicitudesPublicas->crear(
            $datos,
            $request->file('soporte'),
            $request->file('documento_identidad'),
            $request->file('documento_firmado'),
        );

        return response()->json([
            'data' => [
                'referencia' => 'SP-'.Str::padLeft((string) $solicitud->id, 8, '0'),
                'estado' => $solicitud->estado,
            ],
            'message' => 'Solicitud recibida. Será enviada a la Ventanilla Única de Registro (VUR) para su radicación.',
        ], Response::HTTP_CREATED);
    }

    /**
     * Vista previa del PDF antes de enviar: no persiste nada ni notifica a
     * VUR, solo renderiza el documento con los datos ya diligenciados.
     */
    public function preview(PreviewSolicitudPublicaRequest $request): HttpResponse
    {
        $datos = $request->validated();
        $datos['barrio_vereda_sector'] = Sector::findOrFail($datos['sector_id'])->nombre;

        $pdf = $this->solicitudesPublicas->renderPreview($datos);

        return response($pdf, Response::HTTP_OK, ['Content-Type' => 'application/pdf']);
    }
}
