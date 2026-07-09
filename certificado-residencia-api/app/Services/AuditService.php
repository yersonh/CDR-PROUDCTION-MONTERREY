<?php

namespace App\Services;

use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

/**
 * Registra eventos en la bitácora de auditoría (trazabilidad total,
 * sin eliminación física).
 */
class AuditService
{
    /**
     * @param  array<string, mixed>|null  $antes
     * @param  array<string, mixed>|null  $despues
     */
    public function registrar(
        string $accion,
        ?Model $auditable = null,
        ?string $descripcion = null,
        ?array $antes = null,
        ?array $despues = null,
        ?User $actor = null,
    ): Auditoria {
        return Auditoria::create([
            'user_id' => $actor?->id ?? auth()->id(),
            'accion' => $accion,
            'descripcion' => $descripcion,
            'auditable_type' => $auditable ? $auditable::class : null,
            'auditable_id' => $auditable?->getKey(),
            'datos_antes' => $antes,
            'datos_despues' => $despues,
            'ip' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'url' => Request::fullUrl(),
            'metodo' => Request::method(),
        ]);
    }
}
