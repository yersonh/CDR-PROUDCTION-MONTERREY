<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name', 'email', 'password', 'tipo_documento', 'numero_documento',
    'celular', 'dependencia_id', 'activo', 'last_login_at', 'firma_path', 'foto_path',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    // -----------------------------------------------------------------
    // Relaciones
    // -----------------------------------------------------------------

    /** Solicitudes de las que el usuario es el ciudadano titular. */
    public function solicitudes(): HasMany
    {
        return $this->hasMany(Solicitud::class, 'ciudadano_id');
    }

    /** Notificaciones dirigidas a este usuario (bandeja/campanita). */
    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class);
    }

    /** Registro de Presidente JAC vinculado a este login (solo aplica al rol presidente_jac). */
    public function presidenteJac(): HasOne
    {
        return $this->hasOne(PresidenteJac::class);
    }
}
