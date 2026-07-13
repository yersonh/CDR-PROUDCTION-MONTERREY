<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSectorRequest extends FormRequest
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
        return [
            'nombre' => ['required', 'string', 'max:255', 'unique:sectores,nombre'],
            'tipo' => ['required', Rule::in(['barrio', 'vereda'])],
            'zona' => ['required', Rule::in(['urbana', 'rural'])],
        ];
    }
}
