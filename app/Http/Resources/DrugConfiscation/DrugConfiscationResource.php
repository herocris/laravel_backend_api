<?php

namespace App\Http\Resources\DrugConfiscation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para el modelo DrugConfiscation.
 * 
 * Transforma atributos del modelo DrugConfiscation al formato de API.
 * Incluye relaciones anidadas con Confiscation (decomiso), Drug (droga) y DrugPresentation (presentación).
 * Proporciona mapeo bidireccional incluyendo IDs de relaciones.
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
     * @param Request $request
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

    /**
     * Convierte atributo de API (español) a nombre de base de datos (inglés).
     * 
     * Incluye mapeo de claves de relaciones (decomiso, droga, presentacion) a foreign keys.
     * 
     * @param string $index Nombre en español (identificador, cantidad, peso, decomiso, droga, presentacion, foto)
     * @return string|null Nombre en base de datos (id, amount, weight, confiscation_id, drug_id, drug_presentation_id, photo)
     */
    public static function originalAttribute($index)
    {
        $attributes = [
            'identificador' => 'id',
            'cantidad' => 'amount',
            'peso' => 'weight',
            'decomiso' => 'confiscation_id',
            'droga' => 'drug_id',
            'presentacion' => 'drug_presentation_id',
            'foto' => 'photo',
        ];

        return isset($attributes[$index]) ? $attributes[$index] : null;
    }

    /**
     * Convierte atributo de base de datos (inglés) a nombre de API (español).
     * 
     * Incluye mapeo de foreign keys a claves de relaciones en español.
     * 
     * @param string $index Nombre en base de datos (id, amount, weight, confiscation_id, drug_id, drug_presentation_id, photo)
     * @return string|null Nombre en español (identificador, cantidad, peso, decomiso, droga, presentacion, foto)
     */
    public static function transformedAttribute($index)
    {
        $attributes = [
            'id' => 'identificador',
            'amount' => 'cantidad',
            'weight' => 'peso',
            'confiscation_id' => 'decomiso',
            'drug_id' => 'droga',
            'drug_presentation_id' => 'presentacion',
            'photo' => 'foto',
        ];

        return isset($attributes[$index]) ? $attributes[$index] : null;
    }
}
