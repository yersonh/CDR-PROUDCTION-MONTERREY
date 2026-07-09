<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dependencia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DependenciaController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Dependencia::withCount('usuarios')->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validar($request);
        $dependencia = Dependencia::create($data);

        return response()->json(['message' => 'Dependencia creada.', 'data' => $dependencia], 201);
    }

    public function update(Request $request, Dependencia $dependencia): JsonResponse
    {
        $data = $this->validar($request, $dependencia->id);
        $dependencia->update($data);

        return response()->json(['message' => 'Dependencia actualizada.', 'data' => $dependencia]);
    }

    public function destroy(Dependencia $dependencia): JsonResponse
    {
        abort_if($dependencia->usuarios()->exists(), 409, 'No se puede eliminar: tiene usuarios asignados.');
        $dependencia->delete();

        return response()->json(['message' => 'Dependencia eliminada.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validar(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'codigo' => ['nullable', 'string', 'max:100', Rule::unique('dependencias', 'codigo')->ignore($id)],
            'activa' => ['sometimes', 'boolean'],
        ]);
    }
}
