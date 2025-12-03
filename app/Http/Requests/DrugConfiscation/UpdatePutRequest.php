<?php

namespace App\Http\Requests\DrugConfiscation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

/**
 * Request para actualización de registro de droga decomisada.
 *
 * Valida:
 * - amount: requerido, entero.
 * - weight: requerido, numérico.
 * - photo: opcional (imagen PNG, máximo 2048 KB).
 * - confiscation_id, drug_id, drug_presentation_id: requeridos y deben existir.
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
            'amount' => 'required|integer',
            'weight' => 'required|numeric',
            'photo' => [
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
