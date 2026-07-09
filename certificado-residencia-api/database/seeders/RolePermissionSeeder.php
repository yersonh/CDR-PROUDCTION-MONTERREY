<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /** Catálogo de roles del sistema. */
    public const ROLES = [
        'super_admin' => 'Super Administrador',
        'alcalde' => 'Alcalde',
        'recepcionista' => 'Recepcionista',
        'operador' => 'Operador',
        'funcionario_sisben' => 'Funcionario SISBEN',
        'presidente_jac' => 'Presidente JAC',
        'ciudadano' => 'Ciudadano',
    ];

    /** Permisos agrupados por módulo. */
    public const PERMISSIONS = [
        // Solicitudes
        'solicitudes.crear',
        'solicitudes.ver_propias',
        'solicitudes.ver_todas',
        'solicitudes.direccionar',
        // Soportes / documentos
        'soportes.subir',
        'soportes.validar_electoral',
        'soportes.cargar_sisben',
        'soportes.cargar_jac',
        // Validación / prevalidación
        'validacion.prevalidar',
        // Firma
        'firma.ver_bandeja',
        'firma.firmar',
        'casos_especiales.gestionar',
        // Certificados y expedientes
        'certificados.ver',
        'certificados.revocar',
        'expedientes.ver',
        // Dashboard / auditoría
        'dashboard.ver',
        'auditoria.ver',
        // Administración
        'admin.usuarios',
        'admin.roles',
        'admin.dependencias',
    ];

    /** Permisos asignados a cada rol (super_admin recibe todos). */
    public const ROLE_PERMISSIONS = [
        'alcalde' => [
            'solicitudes.ver_todas', 'firma.ver_bandeja', 'firma.firmar',
            'casos_especiales.gestionar', 'certificados.ver', 'certificados.revocar',
            'expedientes.ver', 'dashboard.ver',
        ],
        'recepcionista' => [
            'solicitudes.crear', 'solicitudes.ver_todas', 'solicitudes.direccionar',
            'soportes.subir', 'expedientes.ver', 'dashboard.ver',
        ],
        'operador' => [
            'solicitudes.ver_todas', 'soportes.subir', 'soportes.validar_electoral',
            'validacion.prevalidar', 'expedientes.ver', 'dashboard.ver',
        ],
        'funcionario_sisben' => [
            'solicitudes.ver_todas', 'soportes.cargar_sisben', 'expedientes.ver', 'dashboard.ver',
        ],
        'presidente_jac' => [
            'solicitudes.ver_todas', 'soportes.cargar_jac', 'expedientes.ver', 'dashboard.ver',
        ],
        'ciudadano' => [
            'solicitudes.crear', 'solicitudes.ver_propias', 'soportes.subir',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permiso) {
            Permission::findOrCreate($permiso, 'web');
        }

        foreach (self::ROLES as $key => $label) {
            $role = Role::findOrCreate($key, 'web');

            if ($key === 'super_admin') {
                $role->syncPermissions(Permission::all());
            } else {
                $role->syncPermissions(self::ROLE_PERMISSIONS[$key] ?? []);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
