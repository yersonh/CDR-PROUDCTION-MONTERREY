<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ManualController extends Controller
{
    /** Sirve el manual de usuario correspondiente al rol del usuario autenticado. */
    public function descargar(Request $request): BinaryFileResponse
    {
        $rol = $request->user()->getRoleNames()->first();
        $archivo = $rol ? "{$rol}.pdf" : null;
        $ruta = $archivo ? resource_path("manuales/{$archivo}") : null;

        abort_if(! $ruta || ! file_exists($ruta), 404);

        return response()->download($ruta, 'Manual_Usuario_CDR.pdf');
    }

    /** Sirve el manual de usuario del Ciudadano, sin autenticación. */
    public function descargarPublico(): BinaryFileResponse
    {
        $ruta = resource_path('manuales/ciudadano.pdf');

        abort_if(! file_exists($ruta), 404);

        return response()->download($ruta, 'Manual_Usuario_CDR_Ciudadano.pdf');
    }
}
