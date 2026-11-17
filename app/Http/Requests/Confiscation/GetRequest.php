<?php

namespace App\Http\Requests\Confiscation;

use App\Traits\NormalizesFilterLists;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Request para obtener decomisos filtrados por rango de fechas.
 *
 * Valida:
 * - start_date: requerido, formato Y-m-d.
 * - end_date: requerido, formato Y-m-d.
 */
class GetRequest extends FormRequest
{
    use NormalizesFilterLists;

    protected function prepareForValidation(): void
    {
        $this->normalizeFilterLists(['drugs', 'weapons', 'ammunitions']);
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
            'start_date' => ['required', 'date_format:Y-m-d'],
            'drugs' => ['sometimes', 'array', 'list'],
            'drugs.*' => ['bail', 'required', 'integer', 'min:1', 'distinct', 'exists:drugs,id'],
            'weapons' => ['sometimes', 'array', 'list'],
            'weapons.*' => ['bail', 'required', 'integer', 'min:1', 'distinct', 'exists:weapons,id'],
            'ammunitions' => ['sometimes', 'array', 'list'],
            'ammunitions.*' => ['bail', 'required', 'integer', 'min:1', 'distinct', 'exists:ammunitions,id'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ];
    }
}
