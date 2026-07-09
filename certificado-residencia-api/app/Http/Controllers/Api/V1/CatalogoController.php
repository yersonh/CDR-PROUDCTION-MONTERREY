<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EstadoSolicitud;
use App\Enums\MedioAcreditacion;
use App\Enums\TipoCertificado;
use App\Http\Controllers\Controller;
use App\Models\Dependencia;
use Illuminate\Http\JsonResponse;

class CatalogoController extends Controller
{
    /**
     * Catálogos para alimentar los formularios del frontend.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'tipos_certificado' => $this->enumOptions(TipoCertificado::cases()),
            'medios_acreditacion' => $this->enumOptions(MedioAcreditacion::cases()),
            'estados' => collect(EstadoSolicitud::cases())->map(fn ($e) => [
                'value' => $e->value,
                'label' => $e->label(),
                'color' => $e->color(),
            ]),
            'tipos_documento' => ['CC', 'TI', 'CE', 'PA', 'PEP', 'NIT'],
            'dependencias' => Dependencia::where('activa', true)
                ->orderBy('nombre')
                ->get(['id', 'nombre']),
        ]);
    }

    /**
     * @param  array<int, \App\Enums\TipoCertificado|\App\Enums\MedioAcreditacion>  $cases
     */
    private function enumOptions(array $cases): array
    {
        return array_map(fn ($e) => ['value' => $e->value, 'label' => $e->label()], $cases);
    }
}
