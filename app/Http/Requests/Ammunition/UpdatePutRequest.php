<?php

namespace App\Http\Requests\Ammunition;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

/**
 * Request para actualización de municiones.
 *
 * Valida:
 * - description: requerido, string.
 * - logo: opcional (sometimes, imagen PNG, máximo 2048 KB).
 */
class UpdatePutRequest extends FormRequest
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
            'description' => 'required|string',
            'logo' => [
                'sometimes',
                'image',
                'max:2048',
                File::types(['png'])
            ],
        ];
    }
}
