<?php

namespace App\Http\Requests\Validacion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RedactarObservacionSisbenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('soportes.cargar_sisben') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'resultado' => ['required', Rule::in(['cumple', 'rechaza'])],
        ];
    }
}
