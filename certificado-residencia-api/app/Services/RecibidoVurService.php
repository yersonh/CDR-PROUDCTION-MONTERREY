<?php

namespace App\Services;

use App\Models\RecibidoVur;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class RecibidoVurService
{
    /**
     * Guarda un recibido proveniente de VUR. Idempotente: si radicado_vur
     * ya existe (reintento del Job en VUR tras un fallo transitorio de
     * red), RecibidoVur::create() lanza
     * Illuminate\Database\UniqueConstraintViolationException — el
     * Controller la traduce a 409 en vez de crear un duplicado.
     *
     * @param  array<string, mixed>  $datos
     */
    public function crear(array $datos, UploadedFile $pdf): RecibidoVur
    {
        return DB::transaction(function () use ($datos, $pdf) {
            $ruta = $pdf->store('recibidos-vur', 'local');

            return RecibidoVur::create([
                ...$datos,
                'nombre_original_pdf' => $pdf->getClientOriginalName(),
                'ruta_pdf' => $ruta,
                'estado' => 'pendiente',
            ]);
        });
    }
}
