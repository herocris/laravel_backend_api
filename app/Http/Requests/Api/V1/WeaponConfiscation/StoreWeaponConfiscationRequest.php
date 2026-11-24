<?php

namespace App\Http\Requests\Api\V1\WeaponConfiscation;

use App\Http\Requests\Api\V1\ApiFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
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
class StoreWeaponConfiscationRequest extends ApiFormRequest
{
    /** @return array<string, string> */
    protected function inputAliases(): array
    {
        return [
            'identificador' => 'id',
            'cantidad' => 'amount',
            'decomiso' => 'confiscation_id',
            'arma' => 'weapon_id',
            'foto' => 'photo',
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
            'amount' => 'required|integer|min:1|max:2147483647',
            'confiscation_id' => ['bail', 'required', 'integer', Rule::exists('confiscations', 'id')->withoutTrashed()],
            'weapon_id' => ['bail', 'required', 'integer', Rule::exists('weapons', 'id')->withoutTrashed()],
            'photo' => [
                'required',
                'image',
                'max:2048',
                File::types(['png']),
            ],
        ];
    }
}
