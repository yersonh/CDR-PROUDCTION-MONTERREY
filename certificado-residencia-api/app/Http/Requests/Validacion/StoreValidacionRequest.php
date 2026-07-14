<?php

namespace App\Http\Requests\Validacion;

use App\Enums\ResultadoValidacion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreValidacionRequest extends FormRequest
{
    /** Permiso requerido según el tipo de soporte. */
    private const PERMISO = [
        'electoral' => 'soportes.validar_electoral',
        'sisben' => 'soportes.cargar_sisben',
        'jac' => 'soportes.cargar_jac',
    ];

    public function authorize(): bool
    {
        $permiso = self::PERMISO[$this->input('tipo')] ?? null;

        return $permiso !== null && ($this->user()?->can($permiso) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $esJac = $this->input('tipo') === 'jac';

        return [
            'tipo' => ['required', Rule::in(['electoral', 'sisben', 'jac'])],
            'resultado' => ['nullable', Rule::enum(ResultadoValidacion::class)],
            'observacion' => ['nullable', 'string', 'max:1500'],

            // El funcionario SISBEN/JAC debe adjuntar la certificación
            'soporte' => [
                'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:20480',
                Rule::requiredIf(fn () => in_array($this->input('tipo'), ['sisben', 'jac'], true)),
            ],

            // Campos obligatorios de la certificación JAC
            'codigo_verificacion' => [Rule::requiredIf($esJac), 'nullable', 'string', 'max:100'],
            'fecha_expedicion' => [Rule::requiredIf($esJac), 'nullable', 'date'],
            'fecha_vencimiento' => [Rule::requiredIf($esJac), 'nullable', 'date', 'after_or_equal:fecha_expedicion'],
            'presidente' => [Rule::requiredIf($esJac), 'nullable', 'string', 'max:255'],
            'sector' => [Rule::requiredIf($esJac), 'nullable', 'string', 'max:255'],
            'qr' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Metadatos JAC ensamblados desde los campos validados.
     *
     * @return array<string, mixed>|null
     */
    public function metaJac(): ?array
    {
        if ($this->input('tipo') !== 'jac') {
            return null;
        }

        return $this->only([
            'codigo_verificacion', 'fecha_expedicion', 'fecha_vencimiento', 'presidente', 'sector', 'qr',
        ]);
    }

    public function messages(): array
    {
        return [
            'soporte.required' => 'Debe adjuntar la certificación correspondiente.',
            'codigo_verificacion.required' => 'El código de verificación de la JAC es obligatorio.',
            'fecha_vencimiento.after_or_equal' => 'La fecha de vencimiento debe ser posterior a la de expedición.',
        ];
    }
}
