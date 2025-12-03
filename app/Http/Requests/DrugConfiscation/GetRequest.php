<?php

namespace App\Http\Requests\DrugConfiscation;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request para obtener datos de gráficos de decomisos de droga.
 *
 * Valida:
 * - period: requerido, string, valores permitidos: day|month|quarter|semester|year|total.
 * - start_date, end_date: requeridos, formato Y-m-d.
 * - drugs: requerido, array de IDs de drogas (deben existir en tabla drugs).
 * - typeGraph: requerido, tipo de gráfico (bar|line|pie).
 */
class GetRequest extends FormRequest
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
            'period' => ['required', 'string', 'in:day,month,quarter,semester,year,total'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d'],
            'drugs' => ['required'],
            'drugs.*' => ['integer', 'exists:drugs,id'],
            'typeGraph'=>['required', 'string', 'in:bar,line,pie']
        ];
    }
}
