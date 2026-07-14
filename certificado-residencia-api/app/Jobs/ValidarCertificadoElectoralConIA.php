<?php

namespace App\Jobs;

use App\Enums\MedioAcreditacion;
use App\Enums\ResultadoValidacion;
use App\Models\Solicitud;
use App\Models\User;
use App\Services\GeminiService;
use App\Services\ValidacionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Reemplaza la validación manual de Secretaría para el soporte electoral:
 * le pide a Gemini que revise el certificado electoral adjunto y registra
 * el resultado como si fuera un especialista más (igual que hacen
 * Funcionario SISBEN / Presidente JAC) — Secretaría sigue prevalidando
 * después, con o sin IA.
 *
 * Si el job agota reintentos, la solicitud queda tal cual (sin validación
 * electoral registrada) — Secretaría siempre puede validarla a mano desde
 * ElectoralForm, nada se bloquea.
 */
class ValidarCertificadoElectoralConIA implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [60, 300];

    public function __construct(private readonly int $solicitudId) {}

    public function handle(GeminiService $gemini, ValidacionService $validaciones): void
    {
        $solicitud = Solicitud::with('expediente.documentos')->find($this->solicitudId);

        if (! $solicitud || $solicitud->medio_acreditacion !== MedioAcreditacion::Electoral) {
            return;
        }

        // Ya se validó (por IA en un intento previo, o a mano) — no repetir.
        if ($solicitud->validaciones()->where('tipo', 'electoral')->exists()) {
            return;
        }

        $documento = $solicitud->expediente?->documentos
            ->where('tipo', 'soporte_electoral')
            ->where('vigente', true)
            ->sortByDesc('version')
            ->first();

        if (! $documento) {
            Log::warning("ValidarCertificadoElectoralConIA: la solicitud #{$this->solicitudId} no tiene soporte_electoral, queda para validación manual.");

            return;
        }

        $rutaAbsoluta = Storage::disk('local')->path($documento->path);

        // Documentos guardados antes de que RecibidoVurService detectara el
        // mime real (o cualquier otro dato viejo/genérico) quedaron con
        // "application/octet-stream" — Gemini lo rechaza de plano (HTTP 400
        // "Unsupported MIME type"). Si el mime guardado es ese genérico, se
        // vuelve a detectar directamente del archivo en disco.
        $mime = $documento->mime;
        if (! $mime || $mime === 'application/octet-stream') {
            $mime = mime_content_type($rutaAbsoluta) ?: $mime;
        }

        $resultadoIA = $gemini->validarCertificadoElectoral($rutaAbsoluta, (string) $mime);

        $resultado = $resultadoIA['valido'] ? ResultadoValidacion::Cumple : ResultadoValidacion::Rechaza;
        $observacion = 'Validado automáticamente por IA (Gemini): '.$resultadoIA['motivo'];

        $sistema = User::where('email', 'ia-electoral@sistema.local')->firstOrFail();

        $validaciones->registrarSoporte($solicitud, 'electoral', null, null, $resultado, $observacion, $sistema);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error("ValidarCertificadoElectoralConIA agotó reintentos para la solicitud #{$this->solicitudId}; queda pendiente de validación manual por Secretaría.", [
            'error' => $exception?->getMessage(),
        ]);
    }
}
