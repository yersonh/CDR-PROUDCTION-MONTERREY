<?php

namespace App\Enums;

enum EstadoCertificado: string
{
    case Vigente = 'vigente';
    case Revocado = 'revocado';

    public function label(): string
    {
        return match ($this) {
            self::Vigente => 'Vigente',
            self::Revocado => 'Revocado',
        };
    }
}
