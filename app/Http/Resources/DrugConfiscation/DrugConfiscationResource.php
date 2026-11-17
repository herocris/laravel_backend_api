<?php

namespace App\Http\Resources\DrugConfiscation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para el modelo DrugConfiscation.
 *
 * Transforma atributos del modelo DrugConfiscation al formato de API.
 * Incluye relaciones anidadas con Confiscation (decomiso), Drug (droga) y DrugPresentation (presentación).
 */
class DrugConfiscationResource extends JsonResource
{
    /**
     * Transforma el recurso en un array para respuestas JSON.
     *
     * Incluye:
     * - Datos principales: identificador, cantidad, peso, foto
     * - Relación decomiso: {identificador, descripcion}
     * - Relación droga: {identificador, descripcion}
     * - Relación presentacion: {identificador, descripcion}
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'identificador' => $this->id,
            'cantidad' => $this->amount,
            'peso' => $this->weight,
            'decomiso' => [
                'identificador' => $this->confiscation->id,
                'observacion' => $this->confiscation->observation,
            ],
            'droga' => [
                'identificador' => $this->drug->id,
                'descripcion' => $this->drug->description,
            ],
            'presentacion' => [
                'identificador' => $this->drugPresentation->id,
                'descripcion' => $this->drugPresentation->description,
            ],
            'foto' => $this->photo,
        ];
    }
}
