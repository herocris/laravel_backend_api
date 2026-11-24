<?php

namespace App\Http\Requests\Api\V1\AmmunitionConfiscation;

use App\Http\Requests\Api\V1\ApiFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

/**
 * Request para creación de registro de munición decomisada.
 *
 * Valida:
 * - amount: requerido, entero.
 * - confiscation_id: requerido, debe existir en confiscations.
 * - ammunition_id: requerido, debe existir en ammunitions.
 * - photo: imagen PNG requerida, máximo 2048 KB.
 */
class StoreAmmunitionConfiscationRequest extends ApiFormRequest
{
    /** @return array<string, string> */
    protected function inputAliases(): array
    {
        return [
            'identificador' => 'id',
            'cantidad' => 'amount',
            'decomiso' => 'confiscation_id',
            'municion' => 'ammunition_id',
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
            'ammunition_id' => ['bail', 'required', 'integer', Rule::exists('ammunitions', 'id')->withoutTrashed()],
            'photo' => [
                'required',
                'image',
                'max:2048',
                File::types(['png']),
            ],
        ];
    }
}
