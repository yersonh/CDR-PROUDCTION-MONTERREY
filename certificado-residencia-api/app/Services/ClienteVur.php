<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Cliente de escritura hacia VUR (Ventanilla Única de Registro): envía las
 * solicitudes captadas por el formulario público para que VUR las radique.
 *
 * Contrato confirmado con el equipo de VUR:
 * - POST {VUR_API_URL}/solicitudes-carta-residencia, multipart, respuesta
 *   síncrona 201 {"radicado_vur": "..."}.
 * - Auth: mismo secreto compartido que VUR ya usa para llamar a CDR en
 *   /recibidos-vur (token Sanctum "vur-integration" del usuario de servicio
 *   "Servicio VUR"). VUR lo valida con su middleware EnsureCdrServiceToken
 *   comparando el Bearer contra su config('services.cdr.token') — no hay un
 *   segundo secreto que coordinar, VUR_API_TOKEN debe llevar ese mismo valor.
 */
class ClienteVur
{
    protected string $baseUrl;
    protected string $token;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.vur.url'), '/');
        $this->token = (string) config('services.vur.token');
    }

    private function cliente()
    {
        return Http::withToken($this->token)
            ->acceptJson()
            ->timeout(30);
    }

    /**
     * Envía una solicitud pública (datos + PDF) al endpoint de intake de VUR.
     *
     * NOTA: el documento de identidad (`documento_identidad`) es un adjunto
     * nuevo, agregado después del contrato inicial — VUR aún no lo procesa
     * en su endpoint (solo lee `pdf_solicitud` y `soporte`). Se envía desde
     * ya para no requerir otro cambio en CDR cuando VUR lo soporte.
     *
     * @param  array<string, mixed>  $datos
     * @return array{ok: bool, radicado_vur: ?string, status: int, body: ?string}
     */
    public function enviarSolicitud(
        array $datos,
        string $pdfPath,
        string $pdfNombre,
        ?string $soportePath = null,
        ?string $documentoIdentidadPath = null,
    ): array {
        $handles = [];

        try {
            $pdfHandle = fopen($pdfPath, 'r');
            $handles[] = $pdfHandle;
            $cliente = $this->cliente()->asMultipart()
                ->attach('pdf_solicitud', $pdfHandle, $pdfNombre);

            if ($soportePath) {
                $soporteHandle = fopen($soportePath, 'r');
                $handles[] = $soporteHandle;
                $cliente = $cliente->attach('soporte', $soporteHandle, basename($soportePath));
            }

            if ($documentoIdentidadPath) {
                $documentoIdentidadHandle = fopen($documentoIdentidadPath, 'r');
                $handles[] = $documentoIdentidadHandle;
                $cliente = $cliente->attach('documento_identidad', $documentoIdentidadHandle, basename($documentoIdentidadPath));
            }

            /** @var Response $response */
            $response = $cliente->post("{$this->baseUrl}/v1/solicitudes-carta-residencia", $datos);
        } catch (Throwable $e) {
            // Fallos de red/DNS/timeout no llegan a producir una Response —
            // se tratan igual que un fallo HTTP para que el job reintente.
            Log::error('Error de conexión al enviar solicitud pública a VUR', ['exception' => $e->getMessage()]);

            return ['ok' => false, 'radicado_vur' => null, 'status' => 0, 'body' => $e->getMessage()];
        } finally {
            // Http::fake() no consume el stream, así que Guzzle nunca cierra
            // estos handles por su cuenta — hay que cerrarlos siempre.
            foreach ($handles as $handle) {
                if (is_resource($handle)) {
                    fclose($handle);
                }
            }
        }

        if ($response->failed()) {
            Log::error('Error al enviar solicitud pública a VUR', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return ['ok' => false, 'radicado_vur' => null, 'status' => $response->status(), 'body' => $response->body()];
        }

        return [
            'ok' => true,
            'radicado_vur' => $response->json('radicado_vur') ?? $response->json('radicado'),
            'status' => $response->status(),
            'body' => $response->body(),
        ];
    }

    /**
     * Avisa a VUR que el radicado avanzó de estado (en trámite / respondido /
     * cerrado), para que su propio Radicado deje de quedarse congelado en
     * "RADICADO". Contrato confirmado con VUR:
     * - PATCH {VUR_API_URL}/v1/solicitudes-carta-residencia/{radicado_vur}/estado
     *   (no /v1/radicados/{id} — esa ruta ya la usaban internamente con id
     *   numérico + Sanctum y habría chocado), mismo middleware/token que el
     *   intake.
     * - Body: {"estado": "EN_TRAMITE" | "RESPONDIDO" | "CERRADO"} — ANULADO
     *   queda exclusivo del botón manual de su admin, no lo usamos.
     * - Responde 404 si el radicado no existe, 422 si ya está en estado
     *   terminal o el estado no es válido, 200 si aplica.
     * Cualquier fallo se trata como no bloqueante: se loguea y ya, igual que
     * enviarSolicitud().
     *
     * @return array{ok: bool, status: int, body: ?string}
     */
    public function notificarEstado(string $radicadoVur, string $estadoVur): array
    {
        try {
            $response = $this->cliente()->patch(
                "{$this->baseUrl}/v1/solicitudes-carta-residencia/{$radicadoVur}/estado",
                ['estado' => $estadoVur],
            );
        } catch (Throwable $e) {
            Log::error('Error de conexión al notificar cambio de estado a VUR', [
                'radicado_vur' => $radicadoVur,
                'estado' => $estadoVur,
                'exception' => $e->getMessage(),
            ]);

            return ['ok' => false, 'status' => 0, 'body' => $e->getMessage()];
        }

        if ($response->failed()) {
            Log::error('Error al notificar cambio de estado a VUR', [
                'radicado_vur' => $radicadoVur,
                'estado' => $estadoVur,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return ['ok' => false, 'status' => $response->status(), 'body' => $response->body()];
        }

        return ['ok' => true, 'status' => $response->status(), 'body' => $response->body()];
    }
}
