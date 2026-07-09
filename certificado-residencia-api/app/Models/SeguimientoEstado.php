<?php

namespace App\Models;

use App\Enums\EstadoSolicitud;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeguimientoEstado extends Model
{
    use HasFactory;

    protected $table = 'seguimiento_estados';

    protected $fillable = [
        'solicitud_id', 'estado_anterior', 'estado_nuevo', 'nota', 'actor_id',
    ];

    protected $casts = [
        'estado_anterior' => EstadoSolicitud::class,
        'estado_nuevo' => EstadoSolicitud::class,
    ];

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(Solicitud::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
