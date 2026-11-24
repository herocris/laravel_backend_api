<?php

namespace App\Http\Requests\Api\V1\DrugConfiscation;

use App\Http\Requests\Concerns\NormalizesFilterLists;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Request para obtener datos de gráficos de decomisos de droga.
 *
 * Valida:
 * - period: requerido, string, valores permitidos: month|quarter|semester|year.
 * - start_date, end_date: requeridos, formato Y-m-d.
 * - drugs: requerido, array de IDs de drogas (deben existir en tabla drugs).
 * - typeGraph: requerido, tipo de gráfico (bar|line|pie).
 */
class GraphDrugConfiscationRequest extends FormRequest
{
    use NormalizesFilterLists;

    protected function prepareForValidation(): void
    {
        $this->normalizeFilterLists(['drugs']);
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
            'drugs' => ['present', 'array', 'list'],
            'drugs.*' => ['bail', 'required', 'integer', 'min:1', 'distinct', 'exists:drugs,id'],
            'typeGraph' => ['required', 'string', 'in:bar,line,pie'],
        ];
    }
}
