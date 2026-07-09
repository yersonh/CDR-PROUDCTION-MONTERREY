<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Cálculo de términos administrativos en días hábiles, excluyendo
 * sábados, domingos y festivos oficiales de Colombia.
 */
class SlaCalculator
{
    /** Días hábiles del término del trámite (Certificado de Residencia). */
    public const DIAS_HABILES_TRAMITE = 15;

    /**
     * Suma N días hábiles a una fecha de inicio.
     */
    public static function addBusinessDays(CarbonInterface $start, int $days): CarbonImmutable
    {
        $date = CarbonImmutable::instance($start);
        $added = 0;

        while ($added < $days) {
            $date = $date->addDay();
            if (self::isBusinessDay($date)) {
                $added++;
            }
        }

        return $date;
    }

    /**
     * Fecha límite del trámite (por defecto 15 días hábiles).
     */
    public static function fechaLimiteTramite(CarbonInterface $radicacion): CarbonImmutable
    {
        return self::addBusinessDays($radicacion, self::DIAS_HABILES_TRAMITE)->endOfDay();
    }

    /**
     * Días hábiles restantes entre hoy y una fecha límite (negativo si vencido).
     */
    public static function diasHabilesRestantes(CarbonInterface $limite, ?CarbonInterface $desde = null): int
    {
        $from = CarbonImmutable::instance($desde ?? CarbonImmutable::now())->startOfDay();
        $to = CarbonImmutable::instance($limite)->startOfDay();

        if ($to->equalTo($from)) {
            return 0;
        }

        $sign = $to->greaterThan($from) ? 1 : -1;
        [$a, $b] = $sign === 1 ? [$from, $to] : [$to, $from];

        $count = 0;
        $cursor = $a;
        while ($cursor->lessThan($b)) {
            $cursor = $cursor->addDay();
            if (self::isBusinessDay($cursor)) {
                $count++;
            }
        }

        return $sign * $count;
    }

    public static function isBusinessDay(CarbonImmutable $date): bool
    {
        return ! $date->isWeekend() && ! ColombiaHolidays::isHoliday($date);
    }
}
