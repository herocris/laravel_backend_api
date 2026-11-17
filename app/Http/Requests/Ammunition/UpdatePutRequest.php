<?php

namespace App\Http\Requests\Ammunition;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\File;

/**
 * Request para actualización de municiones.
 *
 * Valida:
 * - description: requerido, string.
 * - logo: opcional (sometimes, imagen PNG, máximo 2048 KB).
 */
class UpdatePutRequest extends ApiFormRequest
{
    /** @return array<string, string> */
    protected function inputAliases(): array
    {
        return [
            'identificador' => 'id',
            'descripcion' => 'description',
            'logo' => 'logo',
        ];
    }

    /**
     * Autorización permitida.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'description' => 'required|string|max:255',
            'logo' => [
                'sometimes',
                'image',
                'max:2048',
                File::types(['png']),
            ],
        ];
    }
}
