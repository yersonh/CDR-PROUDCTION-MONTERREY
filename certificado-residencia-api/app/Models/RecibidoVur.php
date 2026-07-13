<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Bandeja de entrada de solicitudes de Carta de Residencia enviadas
// directamente (peer-to-peer) desde VUR. No es una Solicitud: la
// secretaría revisa estos registros y crea la Solicitud manualmente
// desde el wizard existente, precargando los datos ya disponibles aquí.
class RecibidoVur extends Model
{
    protected $table = 'recibidos_vur';

    protected $fillable = [
        'radicado_vur', 'referencia_cdr', 'nombre_completo', 'tipo_documento',
        'numero_identificacion', 'correo', 'celular',
        'direccion', 'motivo',
        'nombre_original_pdf', 'ruta_pdf', 'estado',
        'solicitud_id', 'procesado_at',
    ];

    protected $casts = [
        'procesado_at' => 'datetime',
    ];

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(Solicitud::class);
    }
}
