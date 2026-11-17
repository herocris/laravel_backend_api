<?php

namespace App\Http\Requests\DrugConfiscation;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

/**
 * Request para creación de registro de droga decomisada.
 *
 * Valida:
 * - amount: requerido, entero (cantidad).
 * - weight: gramos, mayor que cero, hasta dos decimales; sin redondear datos previos.
 * - photo: imagen PNG requerida, máximo 2048 KB.
 * - confiscation_id: requerido, debe existir en tabla confiscations.
 * - drug_id: requerido, debe existir en tabla drugs.
 * - drug_presentation_id: requerido, debe existir en tabla drug_presentations.
 */
class StorePostRequest extends ApiFormRequest
{
    /** @return array<string, string> */
    protected function inputAliases(): array
    {
        return [
            'identificador' => 'id',
            'cantidad' => 'amount',
            'peso' => 'weight',
            'decomiso' => 'confiscation_id',
            'droga' => 'drug_id',
            'presentacion' => 'drug_presentation_id',
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
            'weight' => ['bail', 'required', 'numeric', 'gt:0', 'decimal:0,2', 'max:'.PHP_FLOAT_MAX],
            'photo' => [
                'required',
                'image',
                'max:2048',
                File::types(['png']),
            ],
            'confiscation_id' => ['bail', 'required', 'integer', Rule::exists('confiscations', 'id')->withoutTrashed()],
            'drug_id' => ['bail', 'required', 'integer', Rule::exists('drugs', 'id')->withoutTrashed()],
            'drug_presentation_id' => ['bail', 'required', 'integer', Rule::exists('drug_presentations', 'id')->withoutTrashed()],
        ];
    }
}
