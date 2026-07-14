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

        $documento = $solicitud->expediente?->documentos
            ->where('tipo', 'soporte_electoral')
            ->where('vigente', true)
            ->sortByDesc('version')
            ->first();

        if (! $documento) {
            Log::warning("ValidarCertificadoElectoralConIA: la solicitud #{$this->solicitudId} no tiene soporte_electoral, queda para validación manual.");

            return;
        }

        // Tras una subsanación se vuelve a lanzar este job para el mismo
        // certificado electoral, ya corregido — a diferencia del primer
        // intento, aquí SÍ puede existir una evaluación "electoral" previa.
        // Se compara por versión del documento (no por fecha: dos corridas
        // seguidas pueden caer en el mismo segundo y una comparación de
        // timestamps no sería confiable) — cada versión del soporte debe
        // tener como máximo una evaluación real (con resultado). OJO:
        // subsanar() también crea su propia fila "electoral" sin resultado
        // (solo deja constancia de que el ciudadano cargó algo) — esa no
        // cuenta como evaluación, por eso se filtra por resultado no nulo.
        $evaluacionesPrevias = $solicitud->validaciones()->where('tipo', 'electoral')->whereNotNull('resultado')->count();

        if ($evaluacionesPrevias >= $documento->version) {
            return;
        }

        $trasSubsanacion = $evaluacionesPrevias > 0;

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

        $observacion = $trasSubsanacion
            ? 'Tras la subsanación, se evaluó de nuevo el certificado electoral de '.$solicitud->nombre_completo.' y fue '
                .($resultado === ResultadoValidacion::Cumple ? 'aprobado' : 'rechazado').': '.$resultadoIA['motivo']
            : 'Validado automáticamente por IA: '.$resultadoIA['motivo'];

        $sistema = User::where('email', 'ia-electoral@sistema.local')->firstOrFail();

        $validaciones->registrarSoporte($solicitud, 'electoral', null, null, $resultado, $observacion, $sistema, permiteRevalidar: $trasSubsanacion);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error("ValidarCertificadoElectoralConIA agotó reintentos para la solicitud #{$this->solicitudId}; queda pendiente de validación manual por Secretaría.", [
            'error' => $exception?->getMessage(),
        ]);
    }
}
