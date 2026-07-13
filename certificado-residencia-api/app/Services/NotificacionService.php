<?php

namespace App\Services;

use App\Models\Notificacion;
use App\Models\Solicitud;
use App\Models\User;

class NotificacionService
{
    /**
     * Crea una notificación para cada usuario activo que tenga alguno de
     * los roles dados. Usada para avisar a quien debe actuar sobre una
     * solicitud (funcionario SISBEN, presidente JAC, secretaría) apenas
     * entra al sistema, sin importar si vino del formulario público o de
     * VUR.
     *
     * @param  string[]  $roles
     */
    public function notificarRoles(array $roles, string $mensaje, ?Solicitud $solicitud = null, string $tipo = 'solicitud.nueva'): void
    {
        $usuarios = User::role($roles)->where('activo', true)->get(['id']);

        if ($usuarios->isEmpty()) {
            return;
        }

        $ahora = now();

        Notificacion::insert($usuarios->map(fn (User $u) => [
            'user_id' => $u->id,
            'solicitud_id' => $solicitud?->id,
            'tipo' => $tipo,
            'mensaje' => $mensaje,
            'leida_at' => null,
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ])->all());
    }

    /**
     * Crea una notificación para usuarios puntuales (p. ej. el Presidente
     * JAC del sector exacto de una solicitud, en vez de todos los que
     * tengan ese rol).
     *
     * @param  int[]  $userIds
     */
    public function notificarUsuarios(array $userIds, string $mensaje, ?Solicitud $solicitud = null, string $tipo = 'solicitud.nueva'): void
    {
        if (empty($userIds)) {
            return;
        }

        $ahora = now();

        Notificacion::insert(array_map(fn (int $id) => [
            'user_id' => $id,
            'solicitud_id' => $solicitud?->id,
            'tipo' => $tipo,
            'mensaje' => $mensaje,
            'leida_at' => null,
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ], $userIds));
    }
}
