<?php

namespace App\Http\Requests\Api\V1\DrugPresentation;

use App\Http\Requests\Api\V1\ApiFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\File;

/**
 * Request para creación de presentaciones de droga.
 *
 * Valida:
 * - description: requerido, string, único (nota: valida contra 'ammunitions', corregir si es error).
 * - logo: imagen PNG requerida, máximo 2048 KB.
 */
class StoreDrugPresentationRequest extends ApiFormRequest
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
            'description' => 'required|string|max:255|unique:drug_presentations',
            'logo' => [
                'required',
                'image',
                'max:2048',
                File::types(['png']),
            ],
        ];
    }
}
