<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUsuarioRequest extends FormRequest
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
        $id = $this->route('usuario')->id;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'tipo_documento' => ['nullable', 'string', 'max:10'],
            'numero_documento' => ['nullable', 'string', 'max:40', Rule::unique('users', 'numero_documento')->ignore($id)],
            'celular' => ['nullable', 'string', 'max:30'],
            'dependencia_id' => ['nullable', 'exists:dependencias,id'],
            'activo' => ['sometimes', 'boolean'],
            'rol' => ['sometimes', Rule::exists('roles', 'name')],
        ];
    }
}
