<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para el modelo WeaponConfiscation.
 *
 * Transforma atributos del modelo WeaponConfiscation al formato de API.
 * Incluye relaciones anidadas con Confiscation (decomiso) y Weapon (arma).
 */
class WeaponConfiscationResource extends JsonResource
{
    /**
     * Transforma el recurso en un array para respuestas JSON.
     *
     * Incluye:
     * - Datos principales: identificador, cantidad, foto
     * - Relación decomiso: {identificador, observacion}
     * - Relación arma: {identificador, descripcion}
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'identificador' => $this->whenHas('id'),
            'cantidad' => $this->whenHas('amount'),
            'foto' => $this->whenHas('photo'),
            'arma' => $this->whenLoaded('weapon', fn () => [
                'identificador' => $this->weapon->id,
                'descripcion' => $this->weapon->description,
            ]),
            'decomiso' => $this->whenLoaded('confiscation', fn () => [
                'identificador' => $this->confiscation->id,
                'observacion' => $this->confiscation->observation,
            ]),
        ];
    }
}
