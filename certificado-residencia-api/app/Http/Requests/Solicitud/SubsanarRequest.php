<?php

namespace App\Http\Requests\Solicitud;

use App\Models\Solicitud;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubsanarRequest extends FormRequest
{
    public function authorize(): bool
    {
        $solicitud = $this->route('solicitud');
        $user = $this->user();

        return $solicitud instanceof Solicitud
            && $user !== null
            && $solicitud->ciudadano_id === $user->id
            && $user->can('soportes.subir');
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
                Rule::requiredIf(fn () => $medio === 'electoral'),
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
            'soporte.required' => 'Debe adjuntar nuevamente el certificado electoral.',
            'justificacion.required' => 'Debe actualizar la justificación del caso especial.',
        ];
    }
}
