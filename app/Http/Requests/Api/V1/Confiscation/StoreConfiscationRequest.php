<?php

namespace App\Http\Requests\Api\V1\Confiscation;

use App\Http\Requests\Api\V1\ApiFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Request para creación de decomisos.
 *
 * Valida:
 * - date, observation, direction, department, municipality: requeridos.
 * - latitude, length: requeridos (coordenadas geográficas).
 */
class StoreConfiscationRequest extends ApiFormRequest
{
    /** @return array<string, string> */
    protected function inputAliases(): array
    {
        return [
            'identificador' => 'id',
            'fecha' => 'date',
            'observacion' => 'observation',
            'direccion' => 'direction',
            'departamento' => 'department',
            'municipalidad' => 'municipality',
            'latitud' => 'latitude',
            'longitud' => 'length',
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
            'date' => 'required|date_format:Y-m-d|after_or_equal:1000-01-01|before_or_equal:9999-12-31',
            'observation' => 'required|string|max:255',
            'direction' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'municipality' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'length' => 'required|numeric|between:-180,180',
        ];
    }
}
