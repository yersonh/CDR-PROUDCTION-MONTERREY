<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresidenteJac extends Model
{
    use HasFactory;

    protected $table = 'presidentes_jac';

    protected $fillable = [
        'sector_id', 'nombre_completo', 'tipo_documento', 'numero_identificacion',
        'direccion', 'celular', 'correo', 'fecha_inicio_periodo', 'fecha_fin_periodo',
        'estado', 'user_id',
    ];

    protected $casts = [
        'fecha_inicio_periodo' => 'date',
        'fecha_fin_periodo' => 'date',
    ];

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
