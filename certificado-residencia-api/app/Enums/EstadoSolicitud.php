<?php

namespace App\Enums;

enum EstadoSolicitud: string
{
    case Radicada = 'radicada';
    case EnValidacion = 'en_validacion';
    case PendienteSoporte = 'pendiente_soporte';
    case Preaprobada = 'preaprobada';
    case EnFirma = 'en_firma';
    case Certificada = 'certificada';
    case Rechazada = 'rechazada';

    public function label(): string
    {
        return match ($this) {
            self::Radicada => 'Radicada',
            self::EnValidacion => 'En validación',
            self::PendienteSoporte => 'Pendiente de soporte',
            self::Preaprobada => 'Preaprobada',
            self::EnFirma => 'En firma',
            self::Certificada => 'Certificada',
            self::Rechazada => 'Rechazada',
        };
    }

    /** Color semántico para la UI (badges / timeline). */
    public function color(): string
    {
        return match ($this) {
            self::Radicada => 'blue',
            self::EnValidacion => 'indigo',
            self::PendienteSoporte => 'amber',
            self::Preaprobada => 'cyan',
            self::EnFirma => 'violet',
            self::Certificada => 'green',
            self::Rechazada => 'red',
        };
    }

    /** Estados que se consideran cerrados/terminales. */
    public function esTerminal(): bool
    {
        return in_array($this, [self::Certificada, self::Rechazada], true);
    }
}
