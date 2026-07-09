<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Auditoria extends Model
{
    use HasFactory;

    protected $table = 'auditorias';

    /** Solo se registra created_at (bitácora inmutable). */
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'accion', 'descripcion', 'auditable_type', 'auditable_id',
        'datos_antes', 'datos_despues', 'ip', 'user_agent', 'url', 'metodo',
    ];

    protected $casts = [
        'datos_antes' => 'array',
        'datos_despues' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
