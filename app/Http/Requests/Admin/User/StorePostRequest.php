<?php

namespace App\Http\Requests\Admin\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Request para creación de usuarios.
 *
 * Valida:
 * - name: requerido, string.
 * - email: requerido, email único en tabla users.
 * - password: mínimo 6 caracteres (se puede reforzar con Password::min(8) y reglas adicionales).
 */
class StorePostRequest extends FormRequest
{
    /**
     * Autorización: siempre permitido (lógica delegada a gates/policies si es necesario).
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para creación de usuario.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users',
            // 'password' => ['required',
            // Password::min(8)
            // ->letters()
            // ->mixedCase()
            // ->numbers()
            // ->symbols()],
            'password' => 'required|string|min:6',
        ];
    }
}
