<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SolicitudResource;
use App\Models\ExpedienteDocumento;
use App\Models\Solicitud;
use App\Models\User;
use App\Services\SolicitudService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            if ($user->can('solicitudes.ver_sector')) {
                $query->where('sector_id', $this->sectorDelPresidente($user));
            } else {
                abort_unless($user->can('solicitudes.ver_propias'), Response::HTTP_FORBIDDEN);
                $query->where('ciudadano_id', $user->id);
            }
        }

        // Filtros
        if ($estado = $request->string('estado')->trim()->value()) {
            $query->where('estado', $estado);
        }

        if ($medio = $request->string('medio_acreditacion')->trim()->value()) {
            $query->where('medio_acreditacion', $medio);
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
     * Detalle de una solicitud con su expediente y trazabilidad.
     */
    public function show(Request $request, Solicitud $solicitud): SolicitudResource
    {
        $user = $request->user();

        $this->autorizarAccesoSolicitud($user, $solicitud);

        $solicitud->load([
            'expediente.documentos',
            'seguimientos.actor',
            'validaciones.validadoPor',
            'validaciones.documento',
            'certificado.firmadoPor',
        ]);

        return new SolicitudResource($solicitud);
    }

    /**
     * Descarga un documento del expediente de la solicitud (soporte,
     * documento de identidad, solicitud firmada, certificado, etc.). Mismo
     * criterio de autorización que show(): dueño del expediente o quien
     * tenga solicitudes.ver_todas.
     */
    public function descargarDocumento(Request $request, Solicitud $solicitud, ExpedienteDocumento $documento): StreamedResponse
    {
        $user = $request->user();

        $this->autorizarAccesoSolicitud($user, $solicitud);

        abort_unless($documento->expediente->solicitud_id === $solicitud->id, Response::HTTP_NOT_FOUND);
        abort_unless(Storage::disk($documento->disk)->exists($documento->path), Response::HTTP_NOT_FOUND);

        return Storage::disk($documento->disk)->response($documento->path, $documento->nombre_original);
    }

    /**
     * Mismo criterio de alcance que index(): dueño del expediente, alguien
     * con solicitudes.ver_todas, o el Presidente JAC del sector exacto de
     * la solicitud (solicitudes.ver_sector).
     */
    private function autorizarAccesoSolicitud(User $user, Solicitud $solicitud): void
    {
        if ($user->can('solicitudes.ver_todas')) {
            return;
        }

        if ($user->can('solicitudes.ver_sector')) {
            abort_unless($solicitud->sector_id === $this->sectorDelPresidente($user), Response::HTTP_FORBIDDEN);
            return;
        }

        abort_unless(
            $user->can('solicitudes.ver_propias') && $solicitud->ciudadano_id === $user->id,
            Response::HTTP_FORBIDDEN,
        );
    }

    private function sectorDelPresidente(User $user): ?int
    {
        return $user->presidenteJac()->where('estado', 'activo')->value('sector_id');
    }
}
