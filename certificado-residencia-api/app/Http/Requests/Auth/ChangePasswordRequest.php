<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers(),
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (Hash::check($value, $this->user()->password)) {
                        $fail('La nueva contraseña no puede ser igual a la actual.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.current_password' => 'La contraseña actual no es correcta.',
            'password.confirmed' => 'La confirmación de la nueva contraseña no coincide.',
        ];
    }
}
