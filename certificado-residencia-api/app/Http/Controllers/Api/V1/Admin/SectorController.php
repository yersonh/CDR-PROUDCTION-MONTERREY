<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSectorRequest;
use App\Http\Requests\Admin\UpdateSectorRequest;
use App\Models\Sector;
use Illuminate\Http\JsonResponse;

class SectorController extends Controller
{
    public function index(): JsonResponse
    {
        $sectores = Sector::withCount('presidentesJac')
            ->orderBy('nombre')
            ->get();

        return response()->json(['data' => $sectores]);
    }

    public function store(StoreSectorRequest $request): JsonResponse
    {
        $sector = Sector::create($request->validated());

        return response()->json([
            'data' => $sector,
            'message' => 'Sector creado correctamente.',
        ], 201);
    }

    public function update(UpdateSectorRequest $request, Sector $sector): JsonResponse
    {
        $sector->update($request->validated());

        return response()->json([
            'data' => $sector,
            'message' => 'Sector actualizado correctamente.',
        ]);
    }
}
