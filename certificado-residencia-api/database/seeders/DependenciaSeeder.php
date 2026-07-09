<?php

namespace Database\Seeders;

use App\Models\Dependencia;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DependenciaSeeder extends Seeder
{
    /** Dependencias de la Alcaldía de Monterrey (Casanare). */
    public const DEPENDENCIAS = [
        'Despacho del Alcalde',
        'Oficina Asesora de Jurídica',
        'Oficina Asesora de Planeación',
        'Oficina de Control Interno',
        'Secretaría de Gobierno, Seguridad y Convivencia',
        'Secretaría de Desarrollo Económico y Medio Ambiente',
        'Secretaría de Desarrollo Social',
        'Secretaría de Educación, Cultura y Turismo',
        'Secretaría de Hacienda',
        'Secretaría de Infraestructura',
        'Secretaría General',
    ];

    public function run(): void
    {
        foreach (self::DEPENDENCIAS as $nombre) {
            Dependencia::firstOrCreate(
                ['nombre' => $nombre],
                ['codigo' => Str::upper(Str::slug($nombre, '_')), 'activa' => true],
            );
        }
    }
}
