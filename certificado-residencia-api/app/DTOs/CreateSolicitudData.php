<?php

namespace App\DTOs;

use App\Enums\MedioAcreditacion;
use App\Enums\TipoCertificado;
use Illuminate\Http\UploadedFile;

/**
 * Datos de entrada para radicar una solicitud de certificado de residencia.
 */
readonly class CreateSolicitudData
{
    public function __construct(
        public string $nombreCompleto,
        public ?string $tipoDocumento,
        public string $numeroIdentificacion,
        public string $direccion,
        public string $correo,
        public string $celular,
        public string $barrioVeredaSector,
        public ?string $motivo,
        public TipoCertificado $tipoCertificado,
        public MedioAcreditacion $medioAcreditacion,
        public ?string $justificacionEspecial = null,
        public ?UploadedFile $soporte = null,
        public ?int $ciudadanoId = null,
        public ?int $createdBy = null,
        public ?string $radicadoVur = null,
        public ?int $recibidoVurId = null,
        public ?int $sectorId = null,
    ) {}
}
