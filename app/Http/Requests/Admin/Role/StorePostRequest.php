<?php

namespace App\Http\Requests\Admin\Role;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request para creación de roles.
 *
 * Valida:
 * - name: requerido, string, único en tabla roles.
 */
class StorePostRequest extends FormRequest
{
    /**
     * Autorización permitida por defecto.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|unique:roles',
        ];
    }
}
