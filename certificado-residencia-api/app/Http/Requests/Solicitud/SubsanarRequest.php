<?php

namespace App\Http\Requests\Solicitud;

use App\Models\Solicitud;
use Illuminate\Foundation\Http\FormRequest;

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
        return [
            'soporte' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:20480'],
        ];
    }

    public function messages(): array
    {
        return [
            'soporte.required' => 'Debe adjuntar el documento solicitado.',
        ];
    }
}
