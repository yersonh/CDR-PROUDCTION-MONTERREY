<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\AuditoriaController;
use App\Http\Controllers\Api\V1\CatalogoController;
use App\Http\Controllers\Api\V1\CertificadoController;
use App\Http\Controllers\Api\V1\ConsultaPublicaController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\Admin\DependenciaController;
use App\Http\Controllers\Api\V1\Admin\PresidenteJacController;
use App\Http\Controllers\Api\V1\Admin\RolController;
use App\Http\Controllers\Api\V1\Admin\SectorController;
use App\Http\Controllers\Api\V1\Admin\UsuarioController;
use App\Http\Controllers\Api\V1\NotificacionController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\RecibidoVurController;
use App\Http\Controllers\Api\V1\ReportesController;
use App\Http\Controllers\Api\V1\SolicitudController;
use App\Http\Controllers\Api\V1\SolicitudPublicaController;
use App\Http\Controllers\Api\V1\SubsanacionPublicaController;
use App\Http\Controllers\Api\V1\ValidacionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ---------------------------------------------------------------
    // Consulta pública de autenticidad (sin autenticación)
    // ---------------------------------------------------------------
    Route::get('public/verificar/{codigo}', [ConsultaPublicaController::class, 'verificar']);
    Route::get('public/certificados/{codigo}/pdf', [ConsultaPublicaController::class, 'descargar']);

    // Formulario público de solicitud (sin login): captación ciudadana que
    // se envía a VUR para su radicación. Throttle por IP contra abuso.
    Route::post('public/solicitudes', [SolicitudPublicaController::class, 'store'])
        ->middleware('throttle:5,1');

    // Vista previa del PDF antes de confirmar el envío (no persiste nada).
    Route::post('public/solicitudes/preview', [SolicitudPublicaController::class, 'preview'])
        ->middleware('throttle:20,1');

    // Consulta pública del estado de una solicitud por su referencia de
    // seguimiento (SP-########). Throttle contra fuerza bruta sobre el id
    // secuencial.
    Route::get('public/solicitudes/{referencia}', [SolicitudPublicaController::class, 'consultar'])
        ->middleware('throttle:20,1');

    // Catálogos para alimentar el formulario público (mismos datos que
    // /catalogos, sin autenticación).
    Route::get('public/catalogos', [CatalogoController::class, 'index']);

    // Subsanación pública (sin login): el ciudadano no tiene cuenta en el
    // sistema, así que la autorización la da la firma de la URL (enlace
    // enviado por correo, ver ConceptoRegistradoNotification), no una
    // sesión. Ambas rutas comparten ruta+parámetros para que una misma
    // firma sirva para consultar y para enviar.
    Route::get('public/subsanacion/{solicitud}', [SubsanacionPublicaController::class, 'show'])
        ->name('public.subsanacion.show')
        ->middleware('signed');
    Route::post('public/subsanacion/{solicitud}', [SubsanacionPublicaController::class, 'store'])
        ->middleware(['signed', 'throttle:10,1']);

    // ---------------------------------------------------------------
    // Autenticación
    // ---------------------------------------------------------------
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('me', [AuthController::class, 'me']);
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('refresh', [AuthController::class, 'refresh']);
            Route::post('change-password', [AuthController::class, 'changePassword']);
        });
    });

    // ---------------------------------------------------------------
    // Rutas protegidas (se ampliarán por módulo en fases siguientes)
    // ---------------------------------------------------------------
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('catalogos', [CatalogoController::class, 'index']);

        // Recibidos de VUR (bandeja de entrada — solicitudes de Carta de
        // Residencia enviadas peer-to-peer desde VUR, sin pasar por el Core)
        Route::post('recibidos-vur', [RecibidoVurController::class, 'store'])
            ->middleware('permission:recibidos-vur.crear');
        Route::get('recibidos-vur', [RecibidoVurController::class, 'index'])
            ->middleware('permission:recibidos-vur.ver');
        Route::get('recibidos-vur/{recibidoVur}/pdf', [RecibidoVurController::class, 'descargarPdf'])
            ->middleware('permission:recibidos-vur.ver');

        // Solicitudes / radicación (la radicación manual se retiró — todas las
        // solicitudes entran vía el formulario público + VUR, ver SolicitudPublicaController
        // y RecibidoVurService::procesarAutomaticamente)
        Route::get('solicitudes', [SolicitudController::class, 'index']);
        Route::get('solicitudes/{solicitud}', [SolicitudController::class, 'show']);
        Route::get('solicitudes/{solicitud}/documentos/{documento}/descargar', [SolicitudController::class, 'descargarDocumento']);

        // Validación de soportes y prevalidación
        Route::post('solicitudes/{solicitud}/validaciones', [ValidacionController::class, 'store']);
        Route::post('solicitudes/{solicitud}/sisben/redactar-observacion', [ValidacionController::class, 'redactarObservacionSisben']);
        Route::post('solicitudes/{solicitud}/subsanar', [ValidacionController::class, 'subsanar']);
        Route::post('solicitudes/{solicitud}/prevalidacion', [ValidacionController::class, 'prevalidar'])
            ->middleware('permission:validacion.prevalidar');

        // Firma y certificados
        Route::post('certificados/firmar', [CertificadoController::class, 'firmar'])
            ->middleware('permission:firma.firmar');
        Route::get('solicitudes/{solicitud}/certificado/pdf', [CertificadoController::class, 'descargar']);

        // Dashboard e indicadores
        Route::get('dashboard/indicadores', [DashboardController::class, 'indicadores'])
            ->middleware('permission:dashboard.ver');

        // Reportes gerenciales (Super Admin): SLA, tiempos, productividad, export
        Route::get('reportes', [ReportesController::class, 'indicadores'])
            ->middleware('permission:reportes.ver');
        Route::get('reportes/radicados/export', [ReportesController::class, 'exportarRadicados'])
            ->middleware('permission:reportes.ver');

        // Auditoría
        Route::get('auditoria', [AuditoriaController::class, 'index'])
            ->middleware('permission:auditoria.ver');

        // Notificaciones (campanita) — cada usuario ve/gestiona solo las suyas.
        Route::get('notificaciones/no-leidas', [NotificacionController::class, 'noLeidas']);
        Route::get('notificaciones', [NotificacionController::class, 'index']);
        Route::patch('notificaciones/{notificacion}/leer', [NotificacionController::class, 'marcarLeida']);
        Route::patch('notificaciones/leer-todas', [NotificacionController::class, 'marcarTodasLeidas']);

        // Perfil
        Route::post('perfil/firma', [ProfileController::class, 'subirFirma']);
        Route::get('perfil/firma', [ProfileController::class, 'verFirma']);
        Route::post('perfil/foto', [ProfileController::class, 'subirFoto']);
        Route::get('perfil/foto', [ProfileController::class, 'verFoto']);

        // Administración
        Route::prefix('admin')->group(function () {
            Route::get('usuarios', [UsuarioController::class, 'index'])->middleware('permission:admin.usuarios');
            Route::post('usuarios', [UsuarioController::class, 'store'])->middleware('permission:admin.usuarios');
            Route::put('usuarios/{usuario}', [UsuarioController::class, 'update'])->middleware('permission:admin.usuarios');
            Route::post('usuarios/{usuario}/toggle', [UsuarioController::class, 'toggle'])->middleware('permission:admin.usuarios');

            Route::get('roles', [RolController::class, 'index'])->middleware('permission:admin.roles');

            Route::get('dependencias', [DependenciaController::class, 'index'])->middleware('permission:admin.dependencias');

            Route::get('sectores', [SectorController::class, 'index'])->middleware('permission:admin.sectores');
            Route::post('sectores', [SectorController::class, 'store'])->middleware('permission:admin.sectores');
            Route::put('sectores/{sector}', [SectorController::class, 'update'])->middleware('permission:admin.sectores');

            Route::get('presidentes-jac', [PresidenteJacController::class, 'index'])->middleware('permission:admin.presidentes_jac');
            Route::post('presidentes-jac', [PresidenteJacController::class, 'store'])->middleware('permission:admin.presidentes_jac');
            Route::put('presidentes-jac/{presidenteJac}', [PresidenteJacController::class, 'update'])->middleware('permission:admin.presidentes_jac');
            Route::post('presidentes-jac/{presidenteJac}/reemplazar', [PresidenteJacController::class, 'reemplazar'])->middleware('permission:admin.presidentes_jac');
        });
    });
});
