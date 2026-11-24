<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para el modelo AmmunitionConfiscation.
 *
 * Transforma atributos del modelo AmmunitionConfiscation al formato de API.
 * Incluye relaciones anidadas con Confiscation (decomiso) y Ammunition (munición).
 */
class AmmunitionConfiscationResource extends JsonResource
{
    /**
     * Transforma el recurso en un array para respuestas JSON.
     *
     * Incluye:
     * - Datos principales: identificador, cantidad, foto
     * - Relación decomiso: {identificador, observacion}
     * - Relación municion: {identificador, descripcion}
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'identificador' => $this->whenHas('id'),
            'cantidad' => $this->whenHas('amount'),
            'foto' => $this->whenHas('photo'),
            'decomiso' => $this->whenLoaded('confiscation', fn () => [
                'identificador' => $this->confiscation->id,
                'observacion' => $this->confiscation->observation,
            ]),
            'municion' => $this->whenLoaded('ammunition', fn () => [
                'identificador' => $this->ammunition->id,
                'descripcion' => $this->ammunition->description,
            ]),
        ];
    }
}
