<?php

namespace App\Http\Requests\Solicitud;

use App\Enums\MedioAcreditacion;
use App\Enums\TipoCertificado;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSolicitudRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('solicitudes.crear') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre_completo' => ['required', 'string', 'max:255'],
            'tipo_documento' => ['nullable', 'string', Rule::in(['CC', 'TI', 'CE', 'PA', 'PEP', 'NIT'])],
            'numero_identificacion' => ['required', 'string', 'max:40'],
            'direccion' => ['required', 'string', 'max:255'],
            'correo' => ['required', 'email', 'max:255'],
            'celular' => ['required', 'string', 'max:30'],
            'barrio_vereda_sector' => ['required', 'string', 'max:255'],
            'motivo' => ['nullable', 'string', 'max:1000'],
            'tipo_certificado' => ['required', Rule::enum(TipoCertificado::class)],
            'medio_acreditacion' => ['required', Rule::enum(MedioAcreditacion::class)],

            // Vínculo opcional con un recibido de VUR (bandeja de entrada externa)
            'radicado_vur' => ['nullable', 'string', 'max:30'],
            'recibido_vur_id' => ['nullable', 'integer', 'exists:recibidos_vur,id'],

            // Justificación obligatoria para Caso Especial
            'justificacion_especial' => [
                'nullable', 'string', 'max:1500',
                Rule::requiredIf(fn () => $this->input('medio_acreditacion') === MedioAcreditacion::Especial->value),
            ],

            // Soporte obligatorio para Certificado Electoral (SISBEN/JAC lo cargan sus funcionarios)
            'soporte' => [
                'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:20480',
                Rule::requiredIf(fn () => $this->input('medio_acreditacion') === MedioAcreditacion::Electoral->value),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'justificacion_especial.required' => 'La justificación administrativa es obligatoria para un caso especial.',
            'soporte.required' => 'Debe adjuntar el certificado electoral.',
            'soporte.mimes' => 'El soporte debe ser un archivo PDF, JPG o PNG.',
            'soporte.max' => 'El soporte no puede superar los 20 MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nombre_completo' => 'nombre completo',
            'numero_identificacion' => 'número de identificación',
            'barrio_vereda_sector' => 'barrio, vereda o sector',
            'tipo_certificado' => 'tipo de certificado',
            'medio_acreditacion' => 'medio de acreditación',
        ];
    }
}
