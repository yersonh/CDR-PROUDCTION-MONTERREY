<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ResultadoValidacion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Solicitud\SubsanarRequest;
use App\Http\Requests\Validacion\PrevalidacionRequest;
use App\Http\Requests\Validacion\RedactarObservacionSisbenRequest;
use App\Http\Requests\Validacion\StoreValidacionRequest;
use App\Http\Resources\SolicitudResource;
use App\Http\Resources\ValidacionResource;
use App\Models\Solicitud;
use App\Services\GeminiService;
use App\Services\ValidacionService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ValidacionController extends Controller
{
    public function __construct(private readonly ValidacionService $validaciones) {}

    /**
     * Registrar la validación/carga de un soporte (electoral, SISBEN o JAC).
     */
    public function store(StoreValidacionRequest $request, Solicitud $solicitud): JsonResponse
    {
        $validacion = $this->validaciones->registrarSoporte(
            solicitud: $solicitud,
            tipo: $request->validated('tipo'),
            soporte: $request->file('soporte'),
            meta: $request->metaJac(),
            resultado: $request->filled('resultado')
                ? ResultadoValidacion::from($request->validated('resultado'))
                : null,
            observacion: $request->validated('observacion'),
            actor: $request->user(),
        );

        return (new ValidacionResource($validacion))
            ->additional(['message' => 'Soporte registrado correctamente.'])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Sugiere, con IA, el texto de la observación para la validación SISBEN
     * a partir del resultado (cumple/no cumple) que el funcionario ya
     * seleccionó en el formulario — no decide el resultado, solo redacta.
     */
    public function redactarObservacionSisben(RedactarObservacionSisbenRequest $request, Solicitud $solicitud, GeminiService $gemini): JsonResponse
    {
        $observacion = $gemini->redactarObservacionSisben(
            resultado: $request->validated('resultado'),
            ciudadano: $solicitud->nombre_completo,
            tipoCertificado: $solicitud->tipo_certificado->label(),
        );

        return response()->json(['data' => ['observacion' => $observacion]]);
    }

    /**
     * Subsanación aportada por el ciudadano titular.
     */
    public function subsanar(SubsanarRequest $request, Solicitud $solicitud): JsonResponse
    {
        $solicitud = $this->validaciones->subsanar(
            solicitud: $solicitud,
            soporte: $request->file('soporte'),
            actor: $request->user(),
        );

        $solicitud->load(['expediente.documentos', 'seguimientos.actor', 'validaciones.validadoPor']);

        return (new SolicitudResource($solicitud))
            ->additional(['message' => 'Subsanación registrada. Su solicitud regresó a validación.'])
            ->response();
    }

    /**
     * Emitir concepto de prevalidación (cumple / requiere subsanación / rechaza).
     */
    public function prevalidar(PrevalidacionRequest $request, Solicitud $solicitud): JsonResponse
    {
        $solicitud = $this->validaciones->prevalidar(
            solicitud: $solicitud,
            resultado: ResultadoValidacion::from($request->validated('resultado')),
            observacion: $request->validated('observacion'),
            actor: $request->user(),
            tipoDocumento: $request->validated('tipo_documento'),
        );

        $solicitud->load(['expediente.documentos', 'seguimientos.actor', 'validaciones.validadoPor']);

        return (new SolicitudResource($solicitud))
            ->additional(['message' => 'Concepto de prevalidación registrado.'])
            ->response();
    }
}
