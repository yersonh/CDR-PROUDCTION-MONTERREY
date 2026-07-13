<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReemplazarPresidenteJacRequest;
use App\Http\Requests\Admin\StorePresidenteJacRequest;
use App\Http\Requests\Admin\UpdatePresidenteJacRequest;
use App\Models\PresidenteJac;
use App\Services\PresidenteJacService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PresidenteJacController extends Controller
{
    public function __construct(private readonly PresidenteJacService $presidentesJac) {}

    public function index(Request $request): JsonResponse
    {
        $query = PresidenteJac::query()->with(['sector', 'user'])->latest('id');

        if ($sectorId = $request->integer('sector_id')) {
            $query->where('sector_id', $sectorId);
        }

        if ($estado = $request->string('estado')->trim()->value()) {
            $query->where('estado', $estado);
        }

        return response()->json($query->paginate($request->integer('per_page', 15)));
    }

    public function store(StorePresidenteJacRequest $request): JsonResponse
    {
        $presidente = $this->presidentesJac->crear($request->validated());

        return response()->json([
            'data' => $presidente,
            'message' => 'Presidente JAC creado correctamente. Ya puede iniciar sesión con el correo y la contraseña asignados.',
        ], 201);
    }

    public function update(UpdatePresidenteJacRequest $request, PresidenteJac $presidenteJac): JsonResponse
    {
        $presidenteJac->update($request->validated());

        return response()->json([
            'data' => $presidenteJac->load(['sector', 'user']),
            'message' => 'Datos actualizados correctamente.',
        ]);
    }

    /** Cierra el periodo del presidente actual y da de alta al nuevo, para el mismo sector. */
    public function reemplazar(ReemplazarPresidenteJacRequest $request, PresidenteJac $presidenteJac): JsonResponse
    {
        $nuevo = $this->presidentesJac->reemplazar($presidenteJac, $request->validated());

        return response()->json([
            'data' => $nuevo,
            'message' => 'Presidente JAC reemplazado correctamente.',
        ], 201);
    }
}
