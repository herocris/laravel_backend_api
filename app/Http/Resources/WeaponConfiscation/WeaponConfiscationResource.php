<?php

namespace App\Http\Resources\WeaponConfiscation;

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
            'identificador' => $this->id,
            'cantidad' => $this->amount,
            'decomiso' => [
                'identificador' => $this->confiscation->id,
                'observacion' => $this->confiscation->observation,
            ],
            'arma' => [
                'identificador' => $this->weapon->id,
                'descripcion' => $this->weapon->description,
            ],
            'foto' => $this->photo,
        ];
    }
}
