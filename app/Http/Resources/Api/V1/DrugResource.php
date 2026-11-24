<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para el modelo Drug.
 *
 * Transforma los atributos del modelo Drug al formato de salida de la API.
 * Convierte nombres de campos de base de datos (inglés) a nombres de API (español).
 */
class DrugResource extends JsonResource
{
    /**
     * Transforma el recurso en un array para respuestas JSON.
     *
     * Mapeo de salida:
     * - id -> identificador
     * - description -> descripcion
     * - logo -> logo (sin cambio)
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'identificador' => $this->whenHas('id'),
            'descripcion' => $this->whenHas('description'),
            'logo' => $this->whenHas('logo'),
        ];
    }
}
