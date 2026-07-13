<?php

namespace Database\Seeders;

use App\Models\Sector;
use Illuminate\Database\Seeder;

/**
 * Catálogo inicial de barrios/veredas de Monterrey, Casanare. Lista de
 * arranque — amplíese/corríjase desde Admin > Sectores; no pretende ser el
 * listado oficial completo del municipio.
 */
class SectorSeeder extends Seeder
{
    public const SECTORES = [
        ['nombre' => 'Barrio Centro', 'tipo' => 'barrio', 'zona' => 'urbana'],
        ['nombre' => 'Barrio El Prado', 'tipo' => 'barrio', 'zona' => 'urbana'],
        ['nombre' => 'Barrio Las Ferias', 'tipo' => 'barrio', 'zona' => 'urbana'],
        ['nombre' => 'Barrio Villa del Río', 'tipo' => 'barrio', 'zona' => 'urbana'],
        ['nombre' => 'Barrio San José', 'tipo' => 'barrio', 'zona' => 'urbana'],
        ['nombre' => 'Vereda La Aurora', 'tipo' => 'vereda', 'zona' => 'rural'],
        ['nombre' => 'Vereda San Luis', 'tipo' => 'vereda', 'zona' => 'rural'],
        ['nombre' => 'Vereda El Turpial', 'tipo' => 'vereda', 'zona' => 'rural'],
        ['nombre' => 'Vereda La Esperanza', 'tipo' => 'vereda', 'zona' => 'rural'],
        ['nombre' => 'Vereda Guafal', 'tipo' => 'vereda', 'zona' => 'rural'],
    ];

    public function run(): void
    {
        foreach (self::SECTORES as $sector) {
            Sector::firstOrCreate(['nombre' => $sector['nombre']], $sector);
        }
    }
}
