<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Sector extends Model
{
    use HasFactory;

    protected $table = 'sectores';

    protected $fillable = ['nombre', 'tipo', 'zona', 'activo'];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function presidentesJac(): HasMany
    {
        return $this->hasMany(PresidenteJac::class);
    }

    public function presidenteActivo(): HasOne
    {
        return $this->hasOne(PresidenteJac::class)->where('estado', 'activo');
    }
}
