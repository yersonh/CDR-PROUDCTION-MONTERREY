<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class ClienteCore
{
    protected string $baseUrl;
    protected string $token;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.core.url'), '/');
        $this->token = config('services.core.token');
    }

    private function cliente()
    {
        return Http::withToken($this->token)
            ->acceptJson()
            ->timeout(15);
    }

    private function get(string $path, array $query = [])
    {
        $response = $this->cliente()->get("{$this->baseUrl}/{$path}", $query);

        if ($response->failed()) {
            Log::error("Core API error en GET {$path}", [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new Exception("Error al consultar Core API ({$path}): HTTP {$response->status()}");
        }

        return $response->json();
    }

    // ── Dependencias ────────────────────────────────────────────
    // Respuesta: array plano (sin paginación). Solo lectura desde CDR.

    public function dependencias(): array
    {
        return Cache::remember('core:dependencias', 300, function () {
            return $this->get('dependencias');
        });
    }

    public function dependencia(int $id): ?array
    {
        return collect($this->dependencias())->firstWhere('id', $id);
    }

    // ── Tipos de identificación ─────────────────────────────────
    // Respuesta: array plano. Catálogo fijo, cache larga.

    public function tiposIdentificacion(): array
    {
        return Cache::remember('core:tipos_identificacion', 3600, function () {
            return $this->get('tipos-identificacion');
        });
    }

    // ── Funcionarios ─────────────────────────────────────────────
    // Respuesta: paginada (envoltorio Laravel con "data"). El Core no expone
    // un filtro por número de identificación que funcione (se probó por
    // query string y siempre devuelve la página completa), así que se trae
    // la lista completa (hoy son pocos registros) y se filtra en memoria,
    // igual que dependencia() hace con dependencias().

    public function funcionarios(): array
    {
        return Cache::remember('core:funcionarios', 300, function () {
            return $this->get('funcionarios', ['per_page' => 200])['data'] ?? [];
        });
    }

    /** Busca el funcionario cuya persona tenga este número de identificación (cédula del usuario en CDR). */
    public function funcionario(string $numeroDocumento): ?array
    {
        return collect($this->funcionarios())
            ->firstWhere('persona.numero_identificacion', $numeroDocumento);
    }

    public function verificarConexion(): bool
    {
        try {
            $this->dependencias();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
