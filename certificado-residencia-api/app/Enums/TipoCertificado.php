<?php

namespace App\Enums;

enum TipoCertificado: string
{
    case General = 'general';
    case Estudios = 'estudios';

    public function label(): string
    {
        return match ($this) {
            self::General => 'Certificado de Residencia General',
            self::Estudios => 'Certificado de Residencia para Estudios',
        };
    }
}
