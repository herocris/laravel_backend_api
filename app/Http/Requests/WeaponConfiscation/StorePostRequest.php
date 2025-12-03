<?php

namespace App\Http\Requests\WeaponConfiscation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

/**
 * Request para creación de registro de arma decomisada.
 *
 * Valida:
 * - amount: requerido, entero.
 * - confiscation_id: requerido, debe existir en confiscations.
 * - weapon_id: requerido, debe existir en weapons.
 * - photo: imagen PNG requerida, máximo 2048 KB.
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
            'confiscation_id' => 'required|exists:confiscations,id',
            'weapon_id' => 'required|exists:weapons,id',
            'photo' => [
                'required',
                'image',
                'max:2048',
                File::types(['png'])
            ],
        ];
    }
}
