<?php

namespace App\Http\Requests\WeaponConfiscation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

/**
 * Request para actualización de registro de arma decomisada.
 *
 * Valida:
 * - amount: requerido, string (nota: considerar integer si corresponde).
 * - confiscation_id, weapon_id: requeridos y deben existir.
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
            'weapon_id' => 'required|exists:weapons,id',
            'photo' => [
                'image',
                'max:2048',
                File::types(['png'])
            ],
        ];
    }
}
