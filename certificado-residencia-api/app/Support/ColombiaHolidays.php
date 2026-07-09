<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Calcula los días festivos oficiales de Colombia para un año dado,
 * aplicando la Ley 51 de 1983 (Ley Emiliani) que traslada ciertos
 * festivos al lunes siguiente, y los festivos móviles de Semana Santa.
 */
class ColombiaHolidays
{
    /** @var array<int, list<string>> Caché por año (Y-m-d). */
    private static array $cache = [];

    /**
     * @return list<string> Fechas festivas en formato Y-m-d.
     */
    public static function forYear(int $year): array
    {
        if (isset(self::$cache[$year])) {
            return self::$cache[$year];
        }

        $easter = self::easterSunday($year);

        $holidays = [];

        // --- Festivos fijos (no se trasladan) ---
        foreach ([[1, 1], [5, 1], [7, 20], [8, 7], [12, 8], [12, 25]] as [$m, $d]) {
            $holidays[] = CarbonImmutable::create($year, $m, $d)->format('Y-m-d');
        }

        // --- Festivos trasladables al lunes (Ley Emiliani) ---
        foreach ([[1, 6], [3, 19], [6, 29], [8, 15], [10, 12], [11, 1], [11, 11]] as [$m, $d]) {
            $holidays[] = self::nextMonday(CarbonImmutable::create($year, $m, $d))->format('Y-m-d');
        }

        // --- Festivos móviles basados en la Pascua ---
        // Jueves y Viernes Santo NO se trasladan.
        $holidays[] = $easter->subDays(3)->format('Y-m-d'); // Jueves Santo
        $holidays[] = $easter->subDays(2)->format('Y-m-d'); // Viernes Santo
        // Ascensión (+39), Corpus Christi (+60) y Sagrado Corazón (+68) SÍ se trasladan al lunes.
        $holidays[] = self::nextMonday($easter->addDays(39))->format('Y-m-d');
        $holidays[] = self::nextMonday($easter->addDays(60))->format('Y-m-d');
        $holidays[] = self::nextMonday($easter->addDays(68))->format('Y-m-d');

        sort($holidays);

        return self::$cache[$year] = $holidays;
    }

    public static function isHoliday(CarbonImmutable $date): bool
    {
        return in_array($date->format('Y-m-d'), self::forYear((int) $date->year), true);
    }

    /** Traslada al lunes siguiente si la fecha no cae en lunes. */
    private static function nextMonday(CarbonImmutable $date): CarbonImmutable
    {
        return $date->isMonday() ? $date : $date->next(CarbonImmutable::MONDAY);
    }

    /** Domingo de Pascua (algoritmo anónimo gregoriano de Meeus/Jones/Butcher). */
    private static function easterSunday(int $year): CarbonImmutable
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return CarbonImmutable::create($year, $month, $day);
    }
}
