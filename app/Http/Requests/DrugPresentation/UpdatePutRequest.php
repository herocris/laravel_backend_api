<?php

namespace App\Http\Requests\DrugPresentation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

/**
 * Request para actualización de presentaciones de droga.
 *
 * Valida:
 * - description: requerido, string.
 * - logo: opcional (imagen PNG, máximo 2048 KB).
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
                //'required',
                'image',
                'max:2048',
                File::types(['png'])
            ],
        ];
    }
}
