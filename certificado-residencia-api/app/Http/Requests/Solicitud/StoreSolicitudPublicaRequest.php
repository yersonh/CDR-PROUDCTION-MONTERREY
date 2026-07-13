<?php

namespace App\Http\Requests\Solicitud;

use App\Enums\MedioAcreditacion;
use App\Enums\TipoCertificado;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSolicitudPublicaRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Formulario público: sin sesión, sin permisos. Cualquier ciudadano
        // puede diligenciarlo.
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
            // La cédula de ciudadanía es siempre numérica y de máximo 10
            // dígitos en Colombia. Otros documentos (pasaporte, PEP) pueden
            // incluir letras, por eso el regex solo aplica a CC.
            'numero_identificacion' => array_filter([
                'required', 'string', 'max:40',
                $this->input('tipo_documento') === 'CC' ? 'regex:/^\d{1,10}$/' : null,
            ]),
            'direccion' => ['required', 'string', 'max:255'],
            'correo' => ['required', 'email', 'max:255'],
            'celular' => ['required', 'string', 'max:10', 'regex:/^\d{7,10}$/'],
            'sector_id' => ['required', 'exists:sectores,id'],
            'motivo' => ['nullable', 'string', 'max:1000'],
            'tipo_certificado' => ['required', Rule::enum(TipoCertificado::class)],
            'medio_acreditacion' => ['required', Rule::enum(MedioAcreditacion::class)],

            'justificacion_especial' => [
                'nullable', 'string', 'max:1500',
                Rule::requiredIf(fn () => $this->input('medio_acreditacion') === MedioAcreditacion::Especial->value),
            ],

            // A diferencia del wizard interno (donde el funcionario puede
            // cargar el soporte de SISBEN después), en el formulario público
            // no hay nadie más que lo suba — se exige de una vez para
            // electoral y SISBEN. JAC queda fuera a propósito: el ciudadano
            // normalmente no tiene ese documento (lo expide el Presidente
            // JAC), así que por ahora no se pide aquí — se completa después
            // con la captura de datos del Presidente JAC. "Especial" tampoco
            // lo requiere, porque usa la justificación escrita en su lugar.
            'soporte' => [
                'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:20480',
                Rule::requiredIf(fn () => in_array($this->input('medio_acreditacion'), [
                    MedioAcreditacion::Electoral->value,
                    MedioAcreditacion::Sisben->value,
                ], true)),
            ],

            // Documento de identidad del solicitante, siempre obligatorio
            // (distinto del soporte de acreditación, que solo aplica según
            // el medio elegido).
            'documento_identidad' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:20480'],

            // El PDF que arma CDR es solo un borrador para imprimir — no hay
            // firma electrónica en este canal. El ciudadano debe firmarlo a
            // mano, escanearlo/fotografiarlo como PDF y subirlo aquí; ese es
            // el documento que realmente se envía a VUR. Solo PDF (no
            // jpg/png) porque VUR exige mimes:pdf para pdf_solicitud.
            'documento_firmado' => ['required', 'file', 'mimes:pdf', 'max:20480'],

            // Honeypot anti-spam: campo invisible para el usuario, si viene
            // relleno es un bot. Debe llegar vacío o ausente.
            'sitio_web' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'justificacion_especial.required' => 'La justificación es obligatoria para un caso especial.',
            'soporte.required' => 'Debe adjuntar el soporte de acreditación.',
            'soporte.mimes' => 'El soporte debe ser un archivo PDF, JPG o PNG.',
            'soporte.max' => 'El soporte no puede superar los 20 MB.',
            'documento_identidad.required' => 'Debe adjuntar su documento de identidad.',
            'documento_identidad.mimes' => 'El documento de identidad debe ser un archivo PDF, JPG o PNG.',
            'documento_identidad.max' => 'El documento de identidad no puede superar los 20 MB.',
            'documento_firmado.required' => 'Debe adjuntar el documento firmado.',
            'documento_firmado.mimes' => 'El documento firmado debe ser un archivo PDF.',
            'documento_firmado.max' => 'El documento firmado no puede superar los 20 MB.',
            'numero_identificacion.regex' => 'La cédula debe ser numérica y de máximo 10 dígitos.',
            'celular.regex' => 'El celular debe ser numérico, entre 7 y 10 dígitos.',
            'celular.max' => 'El celular no puede tener más de 10 dígitos.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nombre_completo' => 'nombre completo',
            'numero_identificacion' => 'número de identificación',
            'sector_id' => 'barrio, vereda o sector',
            'tipo_certificado' => 'tipo de certificado',
            'medio_acreditacion' => 'medio de acreditación',
        ];
    }
}
