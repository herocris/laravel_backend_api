<?php

namespace App\Http\Resources\Confiscation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para el modelo Confiscation.
 *
 * Transforma atributos del modelo Confiscation (decomiso) al formato de API.
 * Incluye datos de ubicación geográfica (departamento, municipio, coordenadas) y observaciones.
 */
class ConfiscationResource extends JsonResource
{
    /**
     * Transforma el recurso en un array para respuestas JSON.
     *
     * Mapeo completo:
     * - id -> identificador
     * - date -> fecha
     * - observation -> observacion
     * - direction -> direccion
     * - department -> departamento
     * - municipality -> municipalidad
     * - latitude -> latitud
     * - length -> longitud
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'identificador' => $this->id,
            'fecha' => $this->date,
            'observacion' => $this->observation,
            'direccion' => $this->direction,
            'departamento' => $this->department,
            'municipalidad' => $this->municipality,
            'latitud' => $this->latitude,
            'longitud' => $this->length,
        ];
    }
}
