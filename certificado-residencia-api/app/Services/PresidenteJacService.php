<?php

namespace App\Services;

use App\Models\PresidenteJac;
use App\Models\User;
use App\Notifications\CredencialesTemporalesNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Alta y reemplazo de presidentes JAC. Cada presidente tiene su propio
 * login (rol presidente_jac) con acceso restringido a las solicitudes de su
 * sector — ver el scoping en SolicitudController y ValidacionService. La
 * contraseña siempre la genera el sistema y se envía por correo — nunca la
 * escribe quien crea la cuenta (ver CredencialesTemporalesNotification).
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

        $passwordTemporal = Str::password(12);

        return DB::transaction(function () use ($datos, $passwordTemporal) {
            $user = User::create([
                'name' => $datos['nombre_completo'],
                'email' => $datos['correo'],
                'password' => Hash::make($passwordTemporal),
                'tipo_documento' => $datos['tipo_documento'],
                'numero_documento' => $datos['numero_identificacion'],
                'celular' => $datos['celular'],
                'activo' => true,
                'email_verified_at' => now(),
                'must_change_password' => true,
                'password_expires_at' => now()->addHours(24),
            ]);
            $user->syncRoles(['presidente_jac']);

            $presidente = PresidenteJac::create([
                'sector_id' => $datos['sector_id'],
                'nombre_completo' => $datos['nombre_completo'],
                'tipo_documento' => $datos['tipo_documento'],
                'numero_identificacion' => $datos['numero_identificacion'],
                'direccion' => $datos['direccion'],
                'celular' => $datos['celular'],
                'correo' => $datos['correo'],
                'fecha_inicio_periodo' => $datos['fecha_inicio_periodo'],
                'fecha_fin_periodo' => $datos['fecha_fin_periodo'] ?? null,
                'estado' => 'activo',
                'user_id' => $user->id,
            ])->load(['sector', 'user']);

            $this->enviarCredenciales($user, $passwordTemporal);

            return $presidente;
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

            return $this->crear([...$datos, 'sector_id' => $actual->sector_id]);
        });
    }

    /** No bloquea la creación si el correo falla — queda registrado para diagnóstico. */
    private function enviarCredenciales(User $user, string $passwordTemporal): void
    {
        try {
            Notification::route('mail', $user->email)
                ->notify(new CredencialesTemporalesNotification($user, $passwordTemporal));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
