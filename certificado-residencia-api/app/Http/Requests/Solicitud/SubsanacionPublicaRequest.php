<?php

namespace App\Http\Requests\Solicitud;

use Illuminate\Foundation\Http\FormRequest;

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
