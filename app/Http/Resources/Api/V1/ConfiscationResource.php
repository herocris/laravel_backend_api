<?php

namespace App\Http\Resources\Api\V1;

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
            'identificador' => $this->whenHas('id'),
            'fecha' => $this->whenHas('date'),
            'observacion' => $this->whenHas('observation'),
            'direccion' => $this->whenHas('direction'),
            'departamento' => $this->whenHas('department'),
            'municipalidad' => $this->whenHas('municipality'),
            'latitud' => $this->whenHas('latitude'),
            'longitud' => $this->whenHas('length'),
        ];
    }
}
