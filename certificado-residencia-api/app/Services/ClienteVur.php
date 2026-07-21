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
     * - Cuando el estado es RESPONDIDO, además se adjunta el PDF del
     *   certificado firmado como `documento_respuesta` (multipart) — VUR lo
     *   guarda como su propio documento SALIDA del radicado (la respuesta a
     *   la entrada que él mismo nos envió), igual que ya hace su propio
     *   `RadicadoService::adjuntarPdfSalida()` cuando un operador de VUR
     *   adjunta la respuesta a mano.
     * - Responde 404 si el radicado no existe, 422 si ya está en estado
     *   terminal o el estado no es válido, 200 si aplica.
     * Cualquier fallo se trata como no bloqueante: se loguea y ya, igual que
     * enviarSolicitud().
     *
     * @return array{ok: bool, status: int, body: ?string}
     */
    public function notificarEstado(string $radicadoVur, string $estadoVur, ?string $documentoRespuestaPath = null): array
    {
        $handle = null;
        $url = "{$this->baseUrl}/v1/solicitudes-carta-residencia/{$radicadoVur}/estado";

        try {
            if ($documentoRespuestaPath) {
                // PHP solo puebla $_FILES para un archivo multipart en una
                // petición PATCH real si corre en PHP 8.4+ (request_parse_body).
                // VUR solo exige ^8.3 en su composer.json — no se puede asumir
                // 8.4 del otro lado. Por eso esto va como POST con
                // _method=PATCH (spoofing que Laravel reconoce siempre para
                // peticiones POST reales, ver Request::enableHttpMethodParameterOverride),
                // que sí garantiza el parseo nativo de $_FILES sin importar
                // la versión de PHP de VUR.
                $handle = fopen($documentoRespuestaPath, 'r');
                $response = $this->cliente()->asMultipart()
                    ->attach('documento_respuesta', $handle, basename($documentoRespuestaPath))
                    ->post($url, ['estado' => $estadoVur, '_method' => 'PATCH']);
            } else {
                $response = $this->cliente()->patch($url, ['estado' => $estadoVur]);
            }
        } catch (Throwable $e) {
            Log::error('Error de conexión al notificar cambio de estado a VUR', [
                'radicado_vur' => $radicadoVur,
                'estado' => $estadoVur,
                'exception' => $e->getMessage(),
            ]);

            return ['ok' => false, 'status' => 0, 'body' => $e->getMessage()];
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
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

    /**
     * Indicadores de reportes de VUR (todos los tipos de correspondencia,
     * no solo los de CDR) para el panel de Reportes del Alcalde. Mismos
     * filtros que ReportesAdminController::index del lado de VUR:
     * fecha_desde, fecha_hasta, estado_id, tipo_correspondencia_id,
     * dependencia_destino_id, operador_id.
     *
     * @param  array<string, mixed>  $filtros
     * @return array{ok: bool, data: ?array<string, mixed>, status: int}
     */
    public function reportes(array $filtros): array
    {
        try {
            $response = $this->cliente()->get("{$this->baseUrl}/v1/cdr/reportes", $filtros);
        } catch (Throwable $e) {
            Log::error('Error de conexión al consultar reportes de VUR', ['exception' => $e->getMessage()]);

            return ['ok' => false, 'data' => null, 'status' => 0];
        }

        if ($response->failed()) {
            Log::error('Error al consultar reportes de VUR', ['status' => $response->status(), 'body' => $response->body()]);

            return ['ok' => false, 'data' => null, 'status' => $response->status()];
        }

        return ['ok' => true, 'data' => $response->json(), 'status' => $response->status()];
    }

    /**
     * CSV crudo del listado detallado de radicados de VUR, mismos filtros
     * que reportes().
     *
     * @param  array<string, mixed>  $filtros
     * @return array{ok: bool, body: ?string, status: int}
     */
    public function reportesExportCsv(array $filtros): array
    {
        try {
            $response = $this->cliente()->get("{$this->baseUrl}/v1/cdr/reportes/export", $filtros);
        } catch (Throwable $e) {
            Log::error('Error de conexión al exportar reportes de VUR', ['exception' => $e->getMessage()]);

            return ['ok' => false, 'body' => null, 'status' => 0];
        }

        if ($response->failed()) {
            return ['ok' => false, 'body' => null, 'status' => $response->status()];
        }

        return ['ok' => true, 'body' => $response->body(), 'status' => $response->status()];
    }

    /**
     * Catálogos de VUR (estados y tipos de correspondencia) para alimentar
     * los filtros del panel de reportes — degrada con gracia a listas
     * vacías si VUR no responde.
     *
     * @return array{estados: array<int, mixed>, tipos_correspondencia: array<int, mixed>}
     */
    public function catalogos(): array
    {
        try {
            $estados = $this->cliente()->get("{$this->baseUrl}/v1/cdr/catalogos/estados");
            $tipos = $this->cliente()->get("{$this->baseUrl}/v1/cdr/catalogos/tipos-correspondencia");

            return [
                'estados' => $estados->json('data') ?? [],
                'tipos_correspondencia' => $tipos->json() ?? [],
            ];
        } catch (Throwable $e) {
            Log::error('Error al consultar catálogos de VUR', ['exception' => $e->getMessage()]);

            return ['estados' => [], 'tipos_correspondencia' => []];
        }
    }
}
