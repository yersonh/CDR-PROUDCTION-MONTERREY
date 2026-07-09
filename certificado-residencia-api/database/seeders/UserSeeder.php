<?php

namespace Database\Seeders;

use App\Models\Dependencia;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /** Usuarios demo, uno por rol. Password común: "Yerson-43". */
    public const USERS = [
        ['name' => 'Super Administrador', 'email' => 'admin@monterrey-casanare.gov.co', 'role' => 'super_admin'],
        ['name' => 'Alcalde Municipal', 'email' => 'alcalde@monterrey-casanare.gov.co', 'role' => 'alcalde'],
        ['name' => 'Recepción Ventanilla', 'email' => 'recepcion@monterrey-casanare.gov.co', 'role' => 'recepcionista'],
        ['name' => 'Operador Validador', 'email' => 'operador@monterrey-casanare.gov.co', 'role' => 'operador'],
        ['name' => 'Funcionario SISBEN', 'email' => 'sisben@monterrey-casanare.gov.co', 'role' => 'funcionario_sisben'],
        ['name' => 'Presidente JAC', 'email' => 'jac@monterrey-casanare.gov.co', 'role' => 'presidente_jac'],
        ['name' => 'Ciudadano Demo', 'email' => 'ciudadano@example.com', 'role' => 'ciudadano'],
    ];

    public function run(): void
    {
        $despacho = Dependencia::where('nombre', 'Despacho del Alcalde')->first();

        foreach (self::USERS as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('Yerson-43'),
                    'activo' => true,
                    'email_verified_at' => now(),
                    'dependencia_id' => in_array($data['role'], ['alcalde', 'super_admin'], true)
                        ? $despacho?->id
                        : null,
                ],
            );

            $user->syncRoles([$data['role']]);
        }
    }
}
