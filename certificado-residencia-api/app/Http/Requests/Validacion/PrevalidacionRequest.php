<?php

namespace App\Http\Requests\Validacion;

use App\Enums\ResultadoValidacion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PrevalidacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('validacion.prevalidar') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'resultado' => ['required', Rule::enum(ResultadoValidacion::class)],
            // La observación es obligatoria cuando no cumple (subsanación o rechazo)
            'observacion' => [
                'nullable', 'string', 'max:1500',
                Rule::requiredIf(fn () => $this->input('resultado') !== ResultadoValidacion::Cumple->value),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'observacion.required' => 'Indique el motivo de la subsanación o rechazo.',
        ];
    }
}
