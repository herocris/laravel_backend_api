<?php

namespace App\Http\Requests\DrugConfiscation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

/**
 * Request para creación de registro de droga decomisada.
 *
 * Valida:
 * - amount: requerido, entero (cantidad).
 * - weight: requerido, numérico (peso).
 * - photo: imagen PNG requerida, máximo 2048 KB.
 * - confiscation_id: requerido, debe existir en tabla confiscations.
 * - drug_id: requerido, debe existir en tabla drugs.
 * - drug_presentation_id: requerido, debe existir en tabla drug_presentations.
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
            'amount' => 'required|integer',
            'weight' => 'required|numeric',
            'photo' => [
                'required',
                'image',
                'max:2048',
                File::types(['png'])
            ],
            'confiscation_id' => 'required|exists:confiscations,id',
            'drug_id' => 'required|exists:drugs,id',
            'drug_presentation_id' => 'required|exists:drug_presentations,id',
        ];
    }
}
