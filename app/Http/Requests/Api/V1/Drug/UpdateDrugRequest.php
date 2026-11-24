<?php

namespace App\Http\Requests\Api\V1\Drug;

use App\Http\Requests\Api\V1\ApiFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\File;

/**
 * Request para actualización de drogas.
 *
 * Valida:
 * - description: requerido, string.
 * - logo: opcional (imagen PNG, máximo 2048 KB si se envía).
 */
class UpdateDrugRequest extends ApiFormRequest
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
                // 'required',
                'image',
                'max:2048',
                File::types(['png']),
            ],
        ];
    }
}
