<?php

namespace App\Http\Requests\Api\V1\WeaponConfiscation;

use App\Http\Requests\Concerns\NormalizesFilterLists;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Request para obtener datos de gráficos de decomisos de armas.
 *
 * Valida:
 * - period: requerido, string (month|quarter|semester|year).
 * - start_date, end_date: requeridos, formato Y-m-d.
 * - weapons: requerido, array de IDs de armas (deben existir en tabla weapons).
 * - typeGraph: requerido, tipo de gráfico (bar|line|pie).
 */
class GraphWeaponConfiscationRequest extends FormRequest
{
    use NormalizesFilterLists;

    protected function prepareForValidation(): void
    {
        $this->normalizeFilterLists(['weapons']);
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
            'period' => ['required', 'string', 'in:month,quarter,semester,year'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'weapons' => ['present', 'array', 'list'],
            'weapons.*' => ['bail', 'required', 'integer', 'min:1', 'distinct', 'exists:weapons,id'],
            'typeGraph' => ['required', 'string', 'in:bar,line,pie'],
        ];
    }
}
