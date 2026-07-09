<?php

namespace App\Models;

use App\Enums\EstadoCertificado;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Certificado extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'certificados';

    protected $fillable = [
        'solicitud_id', 'consecutivo', 'codigo_verificacion', 'hash_documento',
        'qr_path', 'pdf_path', 'firmado_por', 'fecha_expedicion',
        'vigencia_hasta', 'estado',
    ];

    protected $casts = [
        'estado' => EstadoCertificado::class,
        'fecha_expedicion' => 'datetime',
        'vigencia_hasta' => 'date',
    ];

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(Solicitud::class);
    }

    public function firmadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'firmado_por');
    }

    public function estaVigente(): bool
    {
        return $this->estado === EstadoCertificado::Vigente
            && (! $this->vigencia_hasta || ! $this->vigencia_hasta->isPast());
    }
}
