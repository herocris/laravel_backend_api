<?php

namespace App\Http\Requests\Weapon;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

/**
 * Request para creación de armas.
 *
 * Valida:
 * - description: requerido, string, único (nota: valida contra 'ammunitions', corregir si es error).
 * - logo: imagen PNG requerida, máximo 2048 KB.
 */
class StorePostRequest extends FormRequest
{
    /**
     * Autorización permitida.
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
            'description' => 'required|string|unique:weapons',
            'logo' => [
                'required',
                'image',
                'max:2048',
                File::types(['png'])
            ],
        ];
    }
}
