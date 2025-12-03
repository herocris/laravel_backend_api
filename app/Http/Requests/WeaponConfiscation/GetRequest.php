<?php

namespace App\Http\Requests\WeaponConfiscation;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request para obtener datos de gráficos de decomisos de armas.
 *
 * Valida:
 * - period: requerido, string (day|month|quarter|semester|year|total).
 * - start_date, end_date: requeridos, formato Y-m-d.
 * - weapons: requerido, array de IDs de armas (deben existir en tabla weapons).
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
            'weapons' => ['required'],
            'weapons.*' => ['integer', 'exists:weapons,id'],
            'typeGraph'=>['required', 'string', 'in:bar,line,pie']
        ];
    }
}
