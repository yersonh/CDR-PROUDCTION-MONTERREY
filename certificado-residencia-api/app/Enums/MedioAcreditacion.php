<?php

namespace App\Enums;

enum MedioAcreditacion: string
{
    case Electoral = 'electoral';
    case Sisben = 'sisben';
    case Jac = 'jac';

    // Solicitud radicada directamente en VUR (correo/ventanilla), sin pasar
    // por el formulario público de CDR — no hay un medio de acreditación
    // real que el ciudadano haya elegido, VUR ya radicó el trámite. No es
    // una opción del formulario público (ver StoreSolicitudPublicaRequest y
    // CatalogoController, que la excluyen explícitamente); solo existe para
    // que RecibidoVurService::procesarAutomaticamente() pueda auto-formalizar
    // este canal igual que sisben/jac/electoral.
    case VurDirecto = 'vur_directo';

    public function label(): string
    {
        return match ($this) {
            self::Electoral => 'Certificado Electoral',
            self::Sisben => 'Certificación de Antigüedad SISBEN',
            self::Jac => 'Certificación Junta de Acción Comunal (JAC)',
            self::VurDirecto => 'Radicado directo en VUR',
        };
    }
}
