<?php

namespace App\Services;

use App\Models\PresidenteJac;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Alta y reemplazo de presidentes JAC. Cada presidente tiene su propio
 * login (rol presidente_jac) con acceso restringido a las solicitudes de su
 * sector — ver el scoping en SolicitudController y ValidacionService.
 */
class PresidenteJacService
{
    /**
     * @param  array<string, mixed>  $datos
     */
    public function crear(array $datos): PresidenteJac
    {
        if (PresidenteJac::where('sector_id', $datos['sector_id'])->where('estado', 'activo')->exists()) {
            throw ValidationException::withMessages([
                'sector_id' => 'Este sector ya tiene un presidente JAC activo. Use "Reemplazar" en vez de crear uno nuevo.',
            ]);
        }

        return DB::transaction(function () use ($datos) {
            $user = User::create([
                'name' => $datos['nombre_completo'],
                'email' => $datos['correo'] ?? $this->emailProvisional($datos),
                'password' => Hash::make($datos['password']),
                'tipo_documento' => $datos['tipo_documento'],
                'numero_documento' => $datos['numero_identificacion'],
                'celular' => $datos['celular'],
                'activo' => true,
                'email_verified_at' => now(),
            ]);
            $user->syncRoles(['presidente_jac']);

            return PresidenteJac::create([
                'sector_id' => $datos['sector_id'],
                'nombre_completo' => $datos['nombre_completo'],
                'tipo_documento' => $datos['tipo_documento'],
                'numero_identificacion' => $datos['numero_identificacion'],
                'direccion' => $datos['direccion'],
                'celular' => $datos['celular'],
                'correo' => $datos['correo'] ?? null,
                'fecha_inicio_periodo' => $datos['fecha_inicio_periodo'],
                'fecha_fin_periodo' => $datos['fecha_fin_periodo'] ?? null,
                'estado' => 'activo',
                'user_id' => $user->id,
            ])->load(['sector', 'user']);
        });
    }

    /**
     * Cierra el periodo del presidente actual (queda histórico, no se
     * borra) y crea uno nuevo para el mismo sector con su propio login.
     *
     * @param  array<string, mixed>  $datos
     */
    public function reemplazar(PresidenteJac $actual, array $datos): PresidenteJac
    {
        return DB::transaction(function () use ($actual, $datos) {
            $actual->update([
                'estado' => 'reemplazado',
                'fecha_fin_periodo' => $actual->fecha_fin_periodo ?? now()->toDateString(),
            ]);

            if ($actual->user) {
                $actual->user->forceFill(['activo' => false])->save();
            }

            $nuevo = $this->crear([...$datos, 'sector_id' => $actual->sector_id]);

            return $nuevo;
        });
    }

    /**
     * Email provisional cuando el presidente no tiene correo propio — el
     * login sigue siendo válido, solo no recibe notificaciones por mail.
     */
    private function emailProvisional(array $datos): string
    {
        return 'jac.'.$datos['numero_identificacion'].'@monterrey-casanare.gov.co';
    }
}
