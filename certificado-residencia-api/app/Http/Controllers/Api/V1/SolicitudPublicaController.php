<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Solicitud\PreviewSolicitudPublicaRequest;
use App\Http\Requests\Solicitud\StoreSolicitudPublicaRequest;
use App\Models\RecibidoVur;
use App\Models\SolicitudPublica;
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
        $pdf = $this->solicitudesPublicas->renderPreview($request->validated());

        return response($pdf, Response::HTTP_OK, ['Content-Type' => 'application/pdf']);
    }

    /**
     * Consulta pública (sin autenticación) del estado de una solicitud por
     * su referencia SP-########. El id es secuencial y por lo tanto
     * adivinable, así que la respuesta no expone datos sensibles (dirección,
     * número de identificación, documentos): solo el nombre y el estado del
     * trámite.
     */
    public function consultar(string $referencia): JsonResponse
    {
        if (! preg_match('/^SP-0*(\d+)$/i', $referencia, $match)) {
            return response()->json([
                'message' => 'No se encontró una solicitud con esa referencia.',
            ], Response::HTTP_NOT_FOUND);
        }

        $solicitud = SolicitudPublica::find((int) $match[1]);

        if (! $solicitud) {
            return response()->json([
                'message' => 'No se encontró una solicitud con esa referencia.',
            ], Response::HTTP_NOT_FOUND);
        }

        $recibido = RecibidoVur::with('solicitud')
            ->where('referencia_cdr', $solicitud->id)
            ->first();

        return response()->json([
            'data' => [
                'referencia' => 'SP-'.Str::padLeft((string) $solicitud->id, 8, '0'),
                'nombre' => $solicitud->nombre_completo,
                'tipo_certificado' => $solicitud->tipo_certificado->label(),
                'creado_at' => $solicitud->created_at,
                ...$this->resolverEstado($solicitud, $recibido),
            ],
        ]);
    }

    /**
     * @return array{codigo: string, label: string, descripcion: string, radicado_vur: ?string, radicado_cdr: ?string}
     */
    private function resolverEstado(SolicitudPublica $solicitud, ?RecibidoVur $recibido): array
    {
        // La Solicitud formal en CDR (una vez secretaría la radica desde el
        // recibido de VUR) es la fuente de verdad más avanzada del trámite.
        if ($recibido?->solicitud) {
            $estadoSolicitud = $recibido->solicitud->estado;

            return [
                'codigo' => $estadoSolicitud->value,
                'label' => $estadoSolicitud->label(),
                'descripcion' => "Su trámite fue radicado y se encuentra en estado \"{$estadoSolicitud->label()}\" en la Alcaldía.",
                'radicado_vur' => $solicitud->radicado_vur,
                'radicado_cdr' => $recibido->solicitud->radicado,
            ];
        }

        if ($recibido) {
            return [
                'codigo' => 'radicada_vur',
                'label' => 'Radicada en VUR',
                'descripcion' => 'Su solicitud fue radicada por la Ventanilla Única de Registro y está pendiente de ser formalizada por la Alcaldía.',
                'radicado_vur' => $solicitud->radicado_vur,
                'radicado_cdr' => null,
            ];
        }

        return match ($solicitud->estado) {
            'enviado' => [
                'codigo' => 'enviado_vur',
                'label' => 'Enviada a VUR',
                'descripcion' => 'Su solicitud fue enviada a la Ventanilla Única de Registro y está pendiente de radicación.',
                'radicado_vur' => $solicitud->radicado_vur,
                'radicado_cdr' => null,
            ],
            'error' => [
                'codigo' => 'error_envio',
                'label' => 'Pendiente de envío',
                'descripcion' => 'Su solicitud fue recibida pero aún no se ha podido enviar a la Ventanilla Única de Registro. Estamos reintentando automáticamente.',
                'radicado_vur' => null,
                'radicado_cdr' => null,
            ],
            default => [
                'codigo' => 'pendiente',
                'label' => 'Recibida',
                'descripcion' => 'Su solicitud fue recibida y está a punto de enviarse a la Ventanilla Única de Registro.',
                'radicado_vur' => null,
                'radicado_cdr' => null,
            ],
        };
    }
}
