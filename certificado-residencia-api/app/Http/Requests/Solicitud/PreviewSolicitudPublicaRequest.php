<?php

namespace App\Http\Requests\Solicitud;

use App\Enums\MedioAcreditacion;
use App\Enums\TipoCertificado;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida los datos para renderizar la vista previa del PDF antes del envío
 * real. Mismos campos que StoreSolicitudPublicaRequest, sin el archivo de
 * soporte (el PDF no lo incluye, solo el texto de la solicitud).
 */
class PreviewSolicitudPublicaRequest extends FormRequest
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
            'nombre_completo' => ['required', 'string', 'max:255'],
            'tipo_documento' => ['nullable', 'string', Rule::in(['CC', 'TI', 'CE', 'PA', 'PEP', 'NIT'])],
            'numero_identificacion' => array_filter([
                'required', 'string', 'max:40',
                $this->input('tipo_documento') === 'CC' ? 'regex:/^\d{1,10}$/' : null,
            ]),
            'direccion' => ['required', 'string', 'max:255'],
            'correo' => ['required', 'email', 'max:255'],
            'celular' => ['required', 'string', 'max:10', 'regex:/^\d{7,10}$/'],
            'barrio_vereda_sector' => ['required', 'string', 'max:255'],
            'sector_id' => [
                'nullable', 'exists:sectores,id',
                Rule::requiredIf(fn () => $this->input('medio_acreditacion') === MedioAcreditacion::Jac->value),
            ],
            'motivo' => ['nullable', 'string', 'max:1000'],
            'tipo_certificado' => ['required', Rule::enum(TipoCertificado::class)],
            'medio_acreditacion' => ['required', Rule::enum(MedioAcreditacion::class)],
            'justificacion_especial' => [
                'nullable', 'string', 'max:1500',
                Rule::requiredIf(fn () => $this->input('medio_acreditacion') === MedioAcreditacion::Especial->value),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'numero_identificacion.regex' => 'La cédula debe ser numérica y de máximo 10 dígitos.',
            'celular.regex' => 'El celular debe ser numérico, entre 7 y 10 dígitos.',
            'celular.max' => 'El celular no puede tener más de 10 dígitos.',
        ];
    }
}
