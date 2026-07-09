<?php

namespace App\Services;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class QrService
{
    /**
     * Genera un código QR y lo devuelve como data URI PNG (para incrustar en el PDF).
     */
    public function dataUri(string $contenido, int $size = 220): string
    {
        $qr = new QrCode(data: $contenido, size: $size, margin: 8);

        return (new PngWriter())->write($qr)->getDataUri();
    }
}
