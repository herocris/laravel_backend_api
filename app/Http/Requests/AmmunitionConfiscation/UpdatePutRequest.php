<?php

namespace App\Http\Requests\AmmunitionConfiscation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

/**
 * Request para actualización de registro de munición decomisada.
 *
 * Valida:
 * - amount: requerido, numérico.
 * - confiscation_id, ammunition_id: requeridos y deben existir.
 * - photo: opcional (imagen PNG, máximo 2048 KB).
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
            'confiscation_id' => 'required|exists:confiscations,id',
            'ammunition_id' => 'required|exists:ammunitions,id',
            'photo' => [
                //'required',
                'image',
                'max:2048',
                File::types(['png'])
            ],
        ];
    }
}
