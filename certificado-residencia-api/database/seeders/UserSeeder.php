<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /** Usuarios demo, uno por rol. Password común: "Yerson-43". */
    public const USERS = [
        ['name' => 'Super Administrador', 'email' => 'admin@monterrey-casanare.gov.co', 'role' => 'super_admin'],
        ['name' => 'Alcalde Municipal', 'email' => 'alcalde@monterrey-casanare.gov.co', 'role' => 'alcalde'],
        ['name' => 'Secretaría', 'email' => 'secretaria@monterrey-casanare.gov.co', 'role' => 'secretaria'],
        ['name' => 'Funcionario SISBEN', 'email' => 'sisben@monterrey-casanare.gov.co', 'role' => 'funcionario_sisben'],
        ['name' => 'Presidente JAC', 'email' => 'jac@monterrey-casanare.gov.co', 'role' => 'presidente_jac'],
    ];

    public function run(): void
    {
        $despacho = collect(app(\App\Services\ClienteCore::class)->dependencias())
            ->firstWhere('nombre', 'Despacho del Alcalde');

        foreach (self::USERS as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('Yerson-43'),
                    'activo' => true,
                    'email_verified_at' => now(),
                    'dependencia_id' => in_array($data['role'], ['alcalde', 'secretaria', 'super_admin'], true)
                        ? ($despacho['id'] ?? null)
                        : null,
                ],
            );

            $user->syncRoles([$data['role']]);

            // Firmar certificados (Alcalde) y prevalidar con concepto "Cumple"
            // (Secretaría) exigen tener una firma electrónica cargada (ver
            // CertificadoService::firmar y ValidacionService::prevalidar) —
            // estas cuentas demo necesitan una para usarse en pruebas sin
            // pasar primero por Mi perfil.
            if (in_array($data['role'], ['alcalde', 'secretaria'], true) && ! $user->firma_path) {
                $rutaFirma = 'firmas/user_'.$user->id.'.png';
                Storage::disk('local')->put(
                    $rutaFirma,
                    base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='),
                );
                $user->forceFill(['firma_path' => $rutaFirma])->save();
            }
        }

        // Cuenta de servicio usada por la integración con VUR: autentica sus
        // llamadas a /recibidos-vur (token Sanctum "vur-integration") y
        // actúa como "actor" en las Solicitud que CDR crea automáticamente
        // al recibir un radicado de VUR (ver RecibidoVurService). Sin rol —
        // solo el permiso puntual que necesita, no todo lo que trae un rol.
        $servicioVur = User::firstOrCreate(
            ['email' => 'servicio-vur@sistema.local'],
            [
                'name' => 'Servicio VUR',
                'password' => Hash::make(Str::random(40)),
                'activo' => true,
                'email_verified_at' => now(),
            ],
        );
        $servicioVur->syncPermissions(['recibidos-vur.crear']);

        // Cuenta de servicio "actor" para las validaciones electorales que
        // registra la IA (ver ValidarCertificadoElectoralConIA) — sin rol ni
        // permisos, el job la usa directamente por servicio, no por HTTP.
        User::firstOrCreate(
            ['email' => 'ia-electoral@sistema.local'],
            [
                'name' => 'Validación IA — Certificado Electoral',
                'password' => Hash::make(Str::random(40)),
                'activo' => true,
                'email_verified_at' => now(),
            ],
        );
    }
}
