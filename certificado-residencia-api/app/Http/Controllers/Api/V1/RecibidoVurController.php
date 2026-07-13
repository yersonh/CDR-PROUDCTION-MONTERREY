<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\RecibidoVurDuplicadoException;
use App\Http\Controllers\Controller;
use App\Http\Requests\RecibidoVur\StoreRecibidoVurRequest;
use App\Models\RecibidoVur;
use App\Services\RecibidoVurService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RecibidoVurController extends Controller
{
    public function __construct(private readonly RecibidoVurService $recibidos) {}

    /**
     * Recibe una solicitud de Carta de Residencia enviada directamente
     * (peer-to-peer) desde VUR. Idempotente: si ya existe un recibido para
     * el mismo envío (por referencia_cdr o radicado_vur, ver
     * RecibidoVurService), responde 409 en vez de crear un duplicado.
     */
    public function store(StoreRecibidoVurRequest $request): JsonResponse
    {
        try {
            $recibido = $this->recibidos->crear($request->validated(), $request->file('pdf'));
        } catch (RecibidoVurDuplicadoException $e) {
            return response()->json([
                'message' => 'Ya existe un recibido para este envío.',
                'data' => $e->existente,
            ], Response::HTTP_CONFLICT);
        }

        // Auto-crea la Solicitud para sisben/jac (ver RecibidoVurService); no
        // bloquea la respuesta si falla — el recibido queda pendiente para
        // manejo manual y el error queda logueado.
        $this->recibidos->procesarAutomaticamente($recibido);

        return response()->json(['data' => $recibido->refresh()], Response::HTTP_CREATED);
    }

    /**
     * Bandeja de recibidos de VUR pendientes de convertir en Solicitud.
     */
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('recibidos-vur.ver'), Response::HTTP_FORBIDDEN);

        $query = RecibidoVur::query()
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->string('estado')))
            ->latest();

        $recibidos = $query->paginate($request->integer('per_page', 20));

        return response()->json([
            'data' => $recibidos->items(),
            'meta' => [
                'current_page' => $recibidos->currentPage(),
                'last_page' => $recibidos->lastPage(),
                'total' => $recibidos->total(),
                'per_page' => $recibidos->perPage(),
            ],
        ]);
    }

    /**
     * Descarga del PDF de entrada adjuntado por VUR.
     */
    public function descargarPdf(Request $request, RecibidoVur $recibidoVur): StreamedResponse
    {
        abort_unless($request->user()->can('recibidos-vur.ver'), Response::HTTP_FORBIDDEN);
        abort_unless(Storage::disk('local')->exists($recibidoVur->ruta_pdf), Response::HTTP_NOT_FOUND);

        return Storage::disk('local')->download($recibidoVur->ruta_pdf, $recibidoVur->nombre_original_pdf);
    }
}
