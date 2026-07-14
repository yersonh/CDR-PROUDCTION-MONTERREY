<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.usuarios') ?? false;
    }

    /**
     * dependencia_id no se pide aquí: la deriva UsuarioController según el
     * rol (Alcalde/Secretaría/Super Admin → Despacho del Alcalde; el resto
     * son funcionarios externos sin dependencia).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'tipo_documento' => ['nullable', 'string', 'max:10'],
            'numero_documento' => ['nullable', 'string', 'max:40', 'unique:users,numero_documento'],
            'celular' => ['nullable', 'string', 'max:30'],
            'rol' => ['required', Rule::exists('roles', 'name')],
        ];
    }
}
