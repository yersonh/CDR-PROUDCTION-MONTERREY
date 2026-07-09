<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\CreateSolicitudData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Solicitud\StoreSolicitudRequest;
use App\Http\Resources\SolicitudResource;
use App\Models\Solicitud;
use App\Services\SolicitudService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SolicitudController extends Controller
{
    public function __construct(private readonly SolicitudService $solicitudes) {}

    /**
     * Listado de solicitudes (propias o todas según permisos), con filtros.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Solicitud::query()
            ->with(['expediente'])
            ->latest('fecha_radicacion');

        // Alcance según permisos
        if (! $user->can('solicitudes.ver_todas')) {
            abort_unless($user->can('solicitudes.ver_propias'), Response::HTTP_FORBIDDEN);
            $query->where('ciudadano_id', $user->id);
        }

        // Filtros
        if ($estado = $request->string('estado')->trim()->value()) {
            $query->where('estado', $estado);
        }

        if ($buscar = $request->string('buscar')->trim()->value()) {
            $query->where(function ($q) use ($buscar) {
                $q->where('radicado', 'like', "%{$buscar}%")
                    ->orWhere('nombre_completo', 'like', "%{$buscar}%")
                    ->orWhere('numero_identificacion', 'like', "%{$buscar}%");
            });
        }

        $solicitudes = $query->paginate($request->integer('per_page', 15));

        return SolicitudResource::collection($solicitudes)->response();
    }

    /**
     * Radicar una nueva solicitud.
     */
    public function store(StoreSolicitudRequest $request): JsonResponse
    {
        $user = $request->user();

        $data = CreateSolicitudData::fromValidated(
            $request->validated(),
            $request->file('soporte'),
            ciudadanoId: $user->hasRole('ciudadano') ? $user->id : null,
            createdBy: $user->id,
        );

        $solicitud = $this->solicitudes->radicar($data);

        return (new SolicitudResource($solicitud))
            ->additional(['message' => "Solicitud radicada con el número {$solicitud->radicado}."])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Detalle de una solicitud con su expediente y trazabilidad.
     */
    public function show(Request $request, Solicitud $solicitud): SolicitudResource
    {
        $user = $request->user();

        $esPropia = $solicitud->ciudadano_id === $user->id;
        abort_unless(
            $user->can('solicitudes.ver_todas') || ($user->can('solicitudes.ver_propias') && $esPropia),
            Response::HTTP_FORBIDDEN,
        );

        $solicitud->load([
            'expediente.documentos',
            'seguimientos.actor',
            'validaciones.validadoPor',
            'validaciones.documento',
            'certificado.firmadoPor',
        ]);

        return new SolicitudResource($solicitud);
    }
}
