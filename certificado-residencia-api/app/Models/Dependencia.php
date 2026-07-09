<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dependencia extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'dependencias';

    protected $fillable = ['nombre', 'codigo', 'activa'];

    protected $casts = ['activa' => 'boolean'];

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function solicitudes(): HasMany
    {
        return $this->hasMany(Solicitud::class);
    }
}
