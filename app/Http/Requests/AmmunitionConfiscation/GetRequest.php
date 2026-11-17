<?php

namespace App\Http\Requests\AmmunitionConfiscation;

use App\Traits\NormalizesFilterLists;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Request para obtener datos de gráficos de decomisos de municiones.
 *
 * Valida:
 * - period: requerido, string (month|quarter|semester|year).
 * - start_date, end_date: requeridos, formato Y-m-d.
 * - ammunitions: requerido, array de IDs de municiones (deben existir en tabla ammunitions).
 * - typeGraph: requerido, tipo de gráfico (bar|line|pie).
 */
class GetRequest extends FormRequest
{
    use NormalizesFilterLists;

    protected function prepareForValidation(): void
    {
        $this->normalizeFilterLists(['ammunitions']);
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
            'ammunitions' => ['present', 'array', 'list'],
            'ammunitions.*' => ['bail', 'required', 'integer', 'min:1', 'distinct', 'exists:ammunitions,id'],
            'typeGraph' => ['required', 'string', 'in:bar,line,pie'],
        ];
    }
}
