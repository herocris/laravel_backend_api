<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para el modelo Weapon.
 *
 * Transforma atributos del modelo Weapon al formato de API.
 * Convierte nombres de campos de inglés (BD) a español (API).
 */
class WeaponResource extends JsonResource
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
            'identificador' => $this->whenHas('id'),
            'descripcion' => $this->whenHas('description'),
            'logo' => $this->whenHas('logo'),
        ];
    }
}
