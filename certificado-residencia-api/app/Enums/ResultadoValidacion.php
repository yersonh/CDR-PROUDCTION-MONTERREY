<?php

namespace App\Enums;

enum ResultadoValidacion: string
{
    case Cumple = 'cumple';
    case Subsanar = 'subsanar';
    case Rechaza = 'rechaza';

    public function label(): string
    {
        return match ($this) {
            self::Cumple => 'Cumple requisitos',
            self::Subsanar => 'Requiere subsanación',
            self::Rechaza => 'Rechazada',
        };
    }
}
