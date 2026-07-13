<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Edición de datos de contacto del presidente actual — no cambia identidad,
 * sector ni login (para eso está "reemplazar").
 */
class UpdatePresidenteJacRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.presidentes_jac') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'direccion' => ['sometimes', 'string', 'max:255'],
            'celular' => ['sometimes', 'string', 'max:30'],
            'correo' => ['nullable', 'email', 'max:255'],
            'fecha_fin_periodo' => ['nullable', 'date', 'after:fecha_inicio_periodo'],
        ];
    }
}
