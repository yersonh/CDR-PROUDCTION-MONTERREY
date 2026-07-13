<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    /** Contador liviano para la campanita — se consulta con polling frecuente. */
    public function noLeidas(Request $request): JsonResponse
    {
        $noLeidas = $request->user()->notificaciones()->whereNull('leida_at')->count();

        return response()->json(['no_leidas' => $noLeidas]);
    }

    /** Lista completa, solo al abrir el panel (no se pollea). */
    public function index(Request $request): JsonResponse
    {
        $notificaciones = $request->user()->notificaciones()
            ->latest()
            ->limit(30)
            ->get()
            ->map(fn (Notificacion $n) => [
                'id' => $n->id,
                'tipo' => $n->tipo,
                'mensaje' => $n->mensaje,
                'solicitud_id' => $n->solicitud_id,
                'leida' => $n->leida_at !== null,
                'created_at' => $n->created_at,
            ]);

        return response()->json(['data' => $notificaciones]);
    }

    public function marcarLeida(Request $request, Notificacion $notificacion): JsonResponse
    {
        abort_unless($notificacion->user_id === $request->user()->id, 403);

        if (! $notificacion->leida_at) {
            $notificacion->update(['leida_at' => now()]);
        }

        return response()->json(['message' => 'Notificación marcada como leída.']);
    }

    public function marcarTodasLeidas(Request $request): JsonResponse
    {
        $request->user()->notificaciones()->whereNull('leida_at')->update(['leida_at' => now()]);

        return response()->json(['message' => 'Notificaciones marcadas como leídas.']);
    }
}
