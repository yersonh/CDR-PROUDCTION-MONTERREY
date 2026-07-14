<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Solicitud\SubsanacionPublicaRequest;
use App\Models\Solicitud;
use App\Services\ValidacionService;
use App\Support\TipoDocumentoCatalogo;
use Illuminate\Http\JsonResponse;

/**
 * Vista pública (sin login) para que el ciudadano corrija y vuelva a enviar
 * el soporte pedido en una subsanación. Solo accesible con el enlace firmado
 * que se envía por correo (ver ConceptoRegistradoNotification) — la firma es
 * la única autorización, protegida además por el middleware `signed`.
 */
class SubsanacionPublicaController extends Controller
{
    public function __construct(private readonly ValidacionService $validaciones) {}

    /** Datos mínimos para que el frontend renderice el formulario. */
    public function show(Solicitud $solicitud): JsonResponse
    {
        $ultimaPrevalidacion = $solicitud->validaciones()
            ->where('tipo', 'prevalidacion')
            ->latest('validado_at')
            ->first();
        $tipoDocumento = $ultimaPrevalidacion?->meta['tipo_documento_solicitado'] ?? null;

        return response()->json(['data' => [
            'radicado' => $solicitud->radicado,
            'nombre_completo' => $solicitud->nombre_completo,
            'medio_acreditacion' => $solicitud->medio_acreditacion->value,
            'estado' => $solicitud->estado->value,
            'observacion' => $ultimaPrevalidacion?->observacion,
            'tipo_documento' => $tipoDocumento,
            'tipo_documento_label' => $tipoDocumento ? TipoDocumentoCatalogo::label($tipoDocumento) : null,
        ]]);
    }

    public function store(SubsanacionPublicaRequest $request, Solicitud $solicitud): JsonResponse
    {
        $solicitud = $this->validaciones->subsanar(
            solicitud: $solicitud,
            soporte: $request->file('soporte'),
            actor: null,
        );

        return response()->json([
            'message' => 'Hemos recibido su corrección. Su solicitud volvió a validación.',
            'data' => [
                'radicado' => $solicitud->radicado,
                'estado' => $solicitud->estado->value,
            ],
        ]);
    }
}
