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
            'dependencia_id' => ['nullable', 'exists:dependencias,id'],
            'rol' => ['required', Rule::exists('roles', 'name')],
        ];
    }
}
