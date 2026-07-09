<?php

namespace App\Services;

use App\Models\Certificado;
use App\Models\Expediente;
use App\Models\Solicitud;
use Illuminate\Support\Facades\DB;

/**
 * Genera consecutivos únicos por año para radicados y expedientes.
 * Formato:
 *   Radicado    → R-2026-000001
 *   Expediente  → EXP-2026-000001
 */
class RadicadoGenerator
{
    /** Radicado de solicitud: R-{año}-{consecutivo 6 dígitos}. */
    public function nuevoRadicado(?int $year = null): string
    {
        $year ??= (int) now()->year;
        $prefijo = "R-{$year}-";

        return $this->siguienteConsecutivo(Solicitud::class, 'radicado', $prefijo, 6);
    }

    /** Código de expediente: EXP-{año}-{consecutivo 6 dígitos}. */
    public function nuevoExpediente(?int $year = null): string
    {
        $year ??= (int) now()->year;
        $prefijo = "EXP-{$year}-";

        return $this->siguienteConsecutivo(Expediente::class, 'codigo', $prefijo, 6);
    }

    /** Consecutivo de certificado: CR-{año}-{consecutivo 8 dígitos}. */
    public function nuevoCertificado(?int $year = null): string
    {
        $year ??= (int) now()->year;
        $prefijo = "CR-{$year}-";

        return $this->siguienteConsecutivo(Certificado::class, 'consecutivo', $prefijo, 8);
    }

    /**
     * Obtiene el siguiente consecutivo para un prefijo dado, con bloqueo
     * pesimista para evitar colisiones en concurrencia.
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    private function siguienteConsecutivo(string $model, string $column, string $prefijo, int $pad): string
    {
        return DB::transaction(function () use ($model, $column, $prefijo, $pad) {
            $ultimo = $model::withTrashed()
                ->where($column, 'like', $prefijo.'%')
                ->orderByDesc($column)
                ->lockForUpdate()
                ->value($column);

            $secuencia = $ultimo
                ? ((int) substr($ultimo, strlen($prefijo))) + 1
                : 1;

            return $prefijo.str_pad((string) $secuencia, $pad, '0', STR_PAD_LEFT);
        });
    }
}
