<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EstadoSolicitud;
use App\Enums\MedioAcreditacion;
use App\Enums\TipoCertificado;
use App\Http\Controllers\Controller;
use App\Services\ClienteCore;
use Illuminate\Http\JsonResponse;

class CatalogoController extends Controller
{
    public function __construct(protected ClienteCore $core)
    {
    }

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
            'tipos_documento' => collect($this->core->tiposIdentificacion())
                ->pluck('codigo')
                ->values(),
            'dependencias' => collect($this->core->dependencias())
                ->map(fn ($d) => ['id' => $d['id'], 'nombre' => $d['nombre']])
                ->sortBy('nombre')
                ->values(),
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
