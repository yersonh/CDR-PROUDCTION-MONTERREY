<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ManualController extends Controller
{
    /**
     * Headers que evitan que el navegador cachee la respuesta por URL. Sin
     * esto, /perfil/manual sirve el mismo PDF cacheado a distintos usuarios
     * que comparten la misma URL (el caché HTTP no distingue por el header
     * Authorization).
     */
    private const SIN_CACHE = [
        'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        'Pragma' => 'no-cache',
    ];

    /** Sirve el manual de usuario correspondiente al rol del usuario autenticado. */
    public function descargar(Request $request): BinaryFileResponse
    {
        $rol = $request->user()->getRoleNames()->first();
        $archivo = $rol ? "{$rol}.pdf" : null;
        $ruta = $archivo ? resource_path("manuales/{$archivo}") : null;

        abort_if(! $ruta || ! file_exists($ruta), 404);

        return response()->download($ruta, 'Manual_Usuario_CDR.pdf', self::SIN_CACHE);
    }

    /** Sirve el manual de usuario del Ciudadano, sin autenticación. */
    public function descargarPublico(): BinaryFileResponse
    {
        $ruta = resource_path('manuales/ciudadano.pdf');

        abort_if(! file_exists($ruta), 404);

        return response()->download($ruta, 'Manual_Usuario_CDR_Ciudadano.pdf', self::SIN_CACHE);
    }
}
