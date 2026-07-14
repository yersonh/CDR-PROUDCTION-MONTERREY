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
        'secretaria' => 'Secretaría',
        'funcionario_sisben' => 'Funcionario SISBEN',
        'presidente_jac' => 'Presidente JAC',
    ];

    /** Permisos agrupados por módulo. */
    public const PERMISSIONS = [
        // Solicitudes
        'solicitudes.ver_propias',
        'solicitudes.ver_todas',
        'solicitudes.ver_sector',
        'solicitudes.direccionar',
        // Recibidos de VUR (bandeja peer-to-peer, no pasa por el Core)
        'recibidos-vur.crear',
        'recibidos-vur.ver',
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
        // Certificados y expedientes
        'certificados.ver',
        'certificados.revocar',
        'expedientes.ver',
        // Dashboard / auditoría / reportes
        'dashboard.ver',
        'auditoria.ver',
        'reportes.ver',
        // Administración
        'admin.usuarios',
        'admin.roles',
        'admin.dependencias',
        'admin.sectores',
        'admin.presidentes_jac',
    ];

    /** Permisos asignados a cada rol (super_admin recibe todos). */
    public const ROLE_PERMISSIONS = [
        'alcalde' => [
            'solicitudes.ver_todas', 'firma.ver_bandeja', 'firma.firmar',
            'certificados.ver', 'certificados.revocar',
            'expedientes.ver', 'dashboard.ver',
        ],
        // Secretaría fusiona lo que antes eran Recepcionista (radica,
        // direcciona) y Operador (valida electoral, prevalida) — la misma
        // persona hace ambas cosas en la práctica.
        // recibidos-vur.ver se retiró: sin "Crear solicitud" (ver commit que
        // lo elimina), Secretaría no tiene ninguna acción real en esa
        // bandeja — SISBEN/JAC se auto-procesan solos. Pendiente reevaluar
        // cuando se defina cómo entra Electoral.
        'secretaria' => [
            'solicitudes.ver_todas', 'solicitudes.direccionar',
            'soportes.subir', 'soportes.validar_electoral',
            'validacion.prevalidar', 'expedientes.ver', 'dashboard.ver',
        ],
        'funcionario_sisben' => [
            'solicitudes.ver_todas', 'soportes.cargar_sisben', 'expedientes.ver', 'dashboard.ver',
        ],
        // Cada Presidente JAC tiene su propio login, atado a un sector
        // (ver PresidenteJac) — solicitudes.ver_sector reemplaza a
        // ver_todas para que solo vea/certifique lo de su propio sector.
        'presidente_jac' => [
            'solicitudes.ver_sector', 'soportes.cargar_jac', 'expedientes.ver', 'dashboard.ver',
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
