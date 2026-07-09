<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpedienteDocumento extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'expediente_documentos';

    protected $fillable = [
        'expediente_id', 'tipo', 'nombre_original', 'path', 'disk',
        'mime', 'size', 'hash', 'es_certificado', 'subido_por',
        'version', 'vigente', 'reemplaza_a',
    ];

    protected $casts = [
        'es_certificado' => 'boolean',
        'vigente' => 'boolean',
        'size' => 'integer',
        'version' => 'integer',
    ];

    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }

    public function subidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subido_por');
    }
}
