<?php

namespace App\Enums;

enum MedioAcreditacion: string
{
    case Electoral = 'electoral';
    case Sisben = 'sisben';
    case Jac = 'jac';
    case Especial = 'especial';

    public function label(): string
    {
        return match ($this) {
            self::Electoral => 'Certificado Electoral',
            self::Sisben => 'Certificación de Antigüedad SISBEN',
            self::Jac => 'Certificación Junta de Acción Comunal (JAC)',
            self::Especial => 'Caso Especial (estudio administrativo)',
        };
    }
}
