<?php

namespace App\Http\Requests\RecibidoVur;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRecibidoVurRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('recibidos-vur.crear') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'radicado_vur' => ['required', 'string', 'max:30'],
            'nombre_completo' => ['required', 'string', 'max:255'],
            'tipo_documento' => ['nullable', 'string', Rule::in(['CC', 'TI', 'CE', 'PA', 'PEP', 'NIT'])],
            'numero_identificacion' => ['nullable', 'string', 'max:40'],
            'correo' => ['nullable', 'email', 'max:255'],
            'celular' => ['nullable', 'string', 'max:30'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'motivo' => ['nullable', 'string', 'max:1000'],
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ];
    }

    public function messages(): array
    {
        return [
            'pdf.mimes' => 'El archivo debe ser un PDF.',
            'pdf.max' => 'El archivo no puede superar los 20 MB.',
        ];
    }
}
