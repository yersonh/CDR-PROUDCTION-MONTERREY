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
