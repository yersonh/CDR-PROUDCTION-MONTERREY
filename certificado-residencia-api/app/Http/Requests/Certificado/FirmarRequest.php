<?php

namespace App\Http\Requests\Certificado;

use Illuminate\Foundation\Http\FormRequest;

class FirmarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('firma.firmar') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'solicitud_ids' => ['required', 'array', 'min:1'],
            'solicitud_ids.*' => ['integer', 'exists:solicitudes,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'solicitud_ids.required' => 'Seleccione al menos una solicitud para firmar.',
        ];
    }
}
