<?php

namespace App\Http\Requests\Solicitud;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Subsanación aportada por el ciudadano vía el enlace público firmado (sin
 * login). La autorización la da la firma de la URL (middleware `signed`),
 * no un usuario autenticado — por eso authorize() siempre es true aquí.
 */
class SubsanacionPublicaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $medio = $this->route('solicitud')?->medio_acreditacion?->value;

        return [
            'soporte' => [
                'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:20480',
                Rule::requiredIf(fn () => in_array($medio, ['electoral', 'sisben', 'jac'], true)),
            ],
            'justificacion' => [
                'nullable', 'string', 'max:1500',
                Rule::requiredIf(fn () => $medio === 'especial'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'soporte.required' => 'Debe adjuntar nuevamente el soporte solicitado.',
            'justificacion.required' => 'Debe actualizar la justificación del caso especial.',
        ];
    }
}
