<?php

namespace App\Http\Requests\Confiscation;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request para creación de decomisos.
 *
 * Valida:
 * - date, observation, direction, department, municipality: requeridos.
 * - latitude, length: requeridos (coordenadas geográficas).
 */
class StorePostRequest extends FormRequest
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
            'date' => 'required',
            'observation' => 'required',
            'direction' => 'required',
            'department' => 'required',
            'municipality' => 'required',
            'latitude' => 'required',
            'length' => 'required',
        ];
    }
}
