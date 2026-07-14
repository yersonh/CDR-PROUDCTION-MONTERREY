<?php

namespace App\Support;

/**
 * Etiquetas legibles para los `tipo` de ExpedienteDocumento que un ciudadano
 * puede llegar a tener que volver a cargar en una subsanación. Los tipos
 * generados por el personal o el sistema (respuesta_oficio_sisben,
 * certificacion_jac, certificado_final) no aparecen aquí a propósito: no
 * tiene sentido pedirle al ciudadano que los "corrija".
 */
class TipoDocumentoCatalogo
{
    public const SUBSANABLES = [
        'soporte_electoral' => 'Certificado electoral',
        'soporte_sisben' => 'Soporte SISBEN',
        'soporte_jac' => 'Soporte JAC',
        'documento_identidad' => 'Documento de identidad',
        'solicitud_firmada' => 'Solicitud firmada',
    ];

    public static function label(string $tipo): string
    {
        return self::SUBSANABLES[$tipo] ?? ucfirst(str_replace('_', ' ', $tipo));
    }
}
