<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StorePresidenteJacRequest extends FormRequest
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
            'sector_id' => ['required', 'exists:sectores,id'],
            'nombre_completo' => ['required', 'string', 'max:255'],
            'tipo_documento' => ['required', 'string', 'max:10'],
            'numero_identificacion' => ['required', 'string', 'max:40', 'unique:users,numero_documento', 'unique:presidentes_jac,numero_identificacion'],
            'direccion' => ['required', 'string', 'max:255'],
            'celular' => ['required', 'string', 'max:30'],
            'correo' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'fecha_inicio_periodo' => ['required', 'date'],
            'fecha_fin_periodo' => ['nullable', 'date', 'after:fecha_inicio_periodo'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ];
    }
}
