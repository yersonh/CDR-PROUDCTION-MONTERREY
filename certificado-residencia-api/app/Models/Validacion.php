<?php

namespace App\Models;

use App\Enums\ResultadoValidacion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Validacion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'validaciones';

    protected $fillable = [
        'solicitud_id', 'tipo', 'resultado', 'observacion',
        'meta', 'documento_id', 'validado_por', 'validado_at',
    ];

    protected $casts = [
        'resultado' => ResultadoValidacion::class,
        'meta' => 'array',
        'validado_at' => 'datetime',
    ];

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(Solicitud::class);
    }

    public function documento(): BelongsTo
    {
        return $this->belongsTo(ExpedienteDocumento::class, 'documento_id');
    }

    public function validadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validado_por');
    }
}
