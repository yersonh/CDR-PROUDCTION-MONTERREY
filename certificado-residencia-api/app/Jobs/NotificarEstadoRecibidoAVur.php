<?php

namespace App\Jobs;

use App\Services\ClienteVur;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Avisa a VUR que un radicado avanzó de estado (EN_TRAMITE / RESPONDIDO /
 * CERRADO). Es puramente informativo para VUR — si falla (incluido mientras
 * su endpoint no exista todavía, ver ClienteVur::notificarEstado), no debe
 * afectar en nada el flujo de CDR. Reintentos limitados, sin marcar ningún
 * estado local en caso de fallo definitivo — solo se deja constancia en el
 * log.
 */
class NotificarEstadoRecibidoAVur implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [30, 120];

    public function __construct(
        private readonly string $radicadoVur,
        private readonly string $estadoVur,
    ) {}

    public function handle(ClienteVur $vur): void
    {
        $resultado = $vur->notificarEstado($this->radicadoVur, $this->estadoVur);

        if (! $resultado['ok']) {
            throw new RuntimeException(
                "No se pudo notificar a VUR el estado {$this->estadoVur} del radicado {$this->radicadoVur}: HTTP {$resultado['status']}",
            );
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::warning('NotificarEstadoRecibidoAVur agotó reintentos, VUR no se enteró del cambio de estado', [
            'radicado_vur' => $this->radicadoVur,
            'estado' => $this->estadoVur,
            'error' => $exception?->getMessage(),
        ]);
    }
}
