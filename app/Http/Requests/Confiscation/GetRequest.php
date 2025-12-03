<?php

namespace App\Http\Requests\Confiscation;

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
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d'],
        ];
    }
}
