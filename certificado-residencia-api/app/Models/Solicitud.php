<?php

namespace App\Models;

use App\Enums\EstadoSolicitud;
use App\Enums\MedioAcreditacion;
use App\Enums\TipoCertificado;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Solicitud extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'solicitudes';

    protected $fillable = [
        'radicado', 'radicado_vur', 'ciudadano_id', 'tipo_certificado', 'medio_acreditacion',
        'nombre_completo', 'tipo_documento', 'numero_identificacion', 'direccion',
        'correo', 'celular', 'barrio_vereda_sector', 'sector_id', 'motivo', 'estado',
        'dependencia_id', 'fecha_radicacion',
        'fecha_limite_sla', 'observaciones', 'created_by',
    ];

    protected $casts = [
        'tipo_certificado' => TipoCertificado::class,
        'medio_acreditacion' => MedioAcreditacion::class,
        'estado' => EstadoSolicitud::class,
        'fecha_radicacion' => 'datetime',
        'fecha_limite_sla' => 'datetime',
    ];

    // -----------------------------------------------------------------
    // Relaciones
    // -----------------------------------------------------------------

    public function ciudadano(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ciudadano_id');
    }

    public function creadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function expediente(): HasOne
    {
        return $this->hasOne(Expediente::class);
    }

    public function certificado(): HasOne
    {
        return $this->hasOne(Certificado::class);
    }

    public function validaciones(): HasMany
    {
        return $this->hasMany(Validacion::class);
    }

    public function seguimientos(): HasMany
    {
        return $this->hasMany(SeguimientoEstado::class)->latest();
    }

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    // -----------------------------------------------------------------
    // Helpers de SLA (15 días hábiles)
    // -----------------------------------------------------------------

    /** Días hábiles restantes hasta el vencimiento (negativo si vencido). */
    public function diasRestantesSla(): ?int
    {
        if (! $this->fecha_limite_sla) {
            return null;
        }

        return \App\Support\SlaCalculator::diasHabilesRestantes($this->fecha_limite_sla);
    }

    /** Semáforo de vencimiento para la UI. */
    public function semaforoSla(): ?string
    {
        $dias = $this->diasRestantesSla();

        if ($dias === null) {
            return null;
        }

        return match (true) {
            $dias < 2 => 'red',
            $dias <= 5 => 'amber',
            default => 'green',
        };
    }
}
