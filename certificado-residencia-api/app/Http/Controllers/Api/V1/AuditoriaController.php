<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditoriaResource;
use App\Models\Auditoria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditoriaController extends Controller
{
    /**
     * Bitácora de auditoría paginada con filtros.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Auditoria::query()
            ->with('user:id,name')
            ->latest('created_at');

        if ($accion = $request->string('accion')->trim()->value()) {
            $query->where('accion', 'like', "%{$accion}%");
        }

        if ($buscar = $request->string('buscar')->trim()->value()) {
            $query->where(function ($q) use ($buscar) {
                $q->where('descripcion', 'like', "%{$buscar}%")
                    ->orWhere('ip', 'like', "%{$buscar}%");
            });
        }

        $auditorias = $query->paginate($request->integer('per_page', 20));

        return AuditoriaResource::collection($auditorias)->response();
    }
}
