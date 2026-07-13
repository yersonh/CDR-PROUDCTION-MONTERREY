<?php

namespace App\Exceptions;

use App\Models\RecibidoVur;
use RuntimeException;

/** Lanzada cuando un recibido de VUR ya existe (por referencia_cdr o radicado_vur) — ver RecibidoVurService::crear(). */
class RecibidoVurDuplicadoException extends RuntimeException
{
    public function __construct(public readonly RecibidoVur $existente)
    {
        parent::__construct("Ya existe un recibido para este envío (id {$existente->id}).");
    }
}
