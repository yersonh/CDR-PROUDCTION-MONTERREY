<?php

namespace App\Models;

use App\Enums\MedioAcreditacion;
use App\Enums\TipoCertificado;
use Illuminate\Database\Eloquent\Model;

class SolicitudPublica extends Model
{
    protected $table = 'solicitudes_publicas';

    protected $fillable = [
        'nombre_completo', 'tipo_documento', 'numero_identificacion',
        'direccion', 'correo', 'celular', 'barrio_vereda_sector', 'motivo',
        'tipo_certificado', 'medio_acreditacion', 'justificacion_especial',
        'ruta_soporte', 'ruta_documento_identidad', 'ruta_pdf', 'ruta_pdf_firmado', 'estado', 'intentos', 'ultimo_error',
        'radicado_vur', 'enviado_at',
    ];

    protected $casts = [
        'tipo_certificado' => TipoCertificado::class,
        'medio_acreditacion' => MedioAcreditacion::class,
        'enviado_at' => 'datetime',
    ];
}
