<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSectorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.sectores') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $id = $this->route('sector')->id;

        return [
            'nombre' => ['sometimes', 'string', 'max:255', Rule::unique('sectores', 'nombre')->ignore($id)],
            'tipo' => ['sometimes', Rule::in(['barrio', 'vereda'])],
            'zona' => ['sometimes', Rule::in(['urbana', 'rural'])],
            'activo' => ['sometimes', 'boolean'],
        ];
    }
}
