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
    ) {}

    /**
     * @param  array<string, mixed>  $v  Datos validados del Form Request.
     */
    public static function fromValidated(array $v, ?UploadedFile $soporte, ?int $ciudadanoId, ?int $createdBy): self
    {
        return new self(
            nombreCompleto: $v['nombre_completo'],
            tipoDocumento: $v['tipo_documento'] ?? null,
            numeroIdentificacion: $v['numero_identificacion'],
            direccion: $v['direccion'],
            correo: $v['correo'],
            celular: $v['celular'],
            barrioVeredaSector: $v['barrio_vereda_sector'],
            motivo: $v['motivo'] ?? null,
            tipoCertificado: TipoCertificado::from($v['tipo_certificado']),
            medioAcreditacion: MedioAcreditacion::from($v['medio_acreditacion']),
            justificacionEspecial: $v['justificacion_especial'] ?? null,
            soporte: $soporte,
            ciudadanoId: $ciudadanoId,
            createdBy: $createdBy,
            radicadoVur: $v['radicado_vur'] ?? null,
            recibidoVurId: $v['recibido_vur_id'] ?? null,
        );
    }
}
