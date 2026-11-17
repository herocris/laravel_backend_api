<?php

namespace App\Http\Resources\Ammunition;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para el modelo Ammunition.
 *
 * Transforma atributos del modelo Ammunition al formato de API.
 * Convierte nombres de campos de inglés (BD) a español (API).
 */
class AmmunitionResource extends JsonResource
{
    /**
     * Transforma el recurso en un array para respuestas JSON.
     *
     * Mapeo: id -> identificador, description -> descripcion, logo -> logo
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'identificador' => $this->id,
            'descripcion' => $this->description,
            'logo' => $this->logo,
        ];
    }
}
