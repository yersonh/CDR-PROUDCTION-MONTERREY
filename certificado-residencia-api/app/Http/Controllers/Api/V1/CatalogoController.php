<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EstadoSolicitud;
use App\Enums\MedioAcreditacion;
use App\Enums\TipoCertificado;
use App\Http\Controllers\Controller;
use App\Models\PresidenteJac;
use App\Models\Sector;
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
            'sectores' => Sector::where('activo', true)
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'tipo', 'zona'])
                ->values(),
            // Para el formulario público: al elegir JAC como medio de
            // acreditación, el ciudadano selecciona su presidente/sector de
            // esta lista (no escribe el sector a mano ni un catálogo de
            // sectores genérico) — así se sabe a quién enrutar la solicitud.
            'presidentes_jac' => PresidenteJac::query()
                ->where('estado', 'activo')
                ->with('sector:id,nombre')
                ->get()
                ->map(fn (PresidenteJac $p) => [
                    'sector_id' => $p->sector_id,
                    'sector_nombre' => $p->sector->nombre,
                    'presidente_nombre' => $p->nombre_completo,
                ])
                ->sortBy('sector_nombre')
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
