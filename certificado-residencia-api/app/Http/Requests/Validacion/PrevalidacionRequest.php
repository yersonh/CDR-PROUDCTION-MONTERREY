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
        $esSubsanar = $this->input('resultado') === ResultadoValidacion::Subsanar->value;

        return [
            'resultado' => ['required', Rule::enum(ResultadoValidacion::class)],
            // La observación es obligatoria cuando no cumple (subsanación o rechazo)
            'observacion' => [
                'nullable', 'string', 'max:1500',
                Rule::requiredIf(fn () => $this->input('resultado') !== ResultadoValidacion::Cumple->value),
            ],
            // Al pedir subsanación hay que decir cuál documento del expediente
            // se debe corregir — solo entre los que la solicitud realmente
            // tiene cargados (no certificados ni respuestas del especialista).
            'tipo_documento' => [
                Rule::requiredIf($esSubsanar), 'nullable', 'string',
                Rule::in(
                    $this->route('solicitud')?->expediente?->documentos
                        ->where('vigente', true)
                        ->where('es_certificado', false)
                        ->pluck('tipo')
                        ->all() ?? []
                ),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'observacion.required' => 'Indique el motivo de la subsanación o rechazo.',
            'tipo_documento.required' => 'Seleccione cuál documento debe corregir el ciudadano.',
            'tipo_documento.in' => 'El documento seleccionado no pertenece al expediente de esta solicitud.',
        ];
    }
}
