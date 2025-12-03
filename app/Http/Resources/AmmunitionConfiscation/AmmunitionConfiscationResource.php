<?php

namespace App\Http\Resources\AmmunitionConfiscation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para el modelo AmmunitionConfiscation.
 * 
 * Transforma atributos del modelo AmmunitionConfiscation al formato de API.
 * Incluye relaciones anidadas con Confiscation (decomiso) y Ammunition (munición).
 * Proporciona mapeo bidireccional incluyendo IDs de relaciones.
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
     * @param Request $request
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
            'municion' => [
                'identificador' => $this->ammunition->id,
                'descripcion' => $this->ammunition->description,
            ],
            'foto' => $this->photo,
        ];
    }

    /**
     * Convierte atributo de API (español) a nombre de base de datos (inglés).
     * 
     * Incluye mapeo de claves de relaciones (decomiso, municion) a foreign keys.
     * 
     * @param string $index Nombre en español (identificador, cantidad, decomiso, municion, foto)
     * @return string|null Nombre en base de datos (id, amount, confiscation_id, ammunition_id, photo)
     */
    public static function originalAttribute($index)
    {
        $attributes = [
            'identificador' => 'id',
            'cantidad' => 'amount',
            'decomiso' => 'confiscation_id',
            'municion' => 'ammunition_id',
            'foto' => 'photo',
        ];

        return isset($attributes[$index]) ? $attributes[$index] : null;
    }

    /**
     * Convierte atributo de base de datos (inglés) a nombre de API (español).
     * 
     * Incluye mapeo de foreign keys a claves de relaciones en español.
     * 
     * @param string $index Nombre en base de datos (id, amount, confiscation_id, ammunition_id, photo)
     * @return string|null Nombre en español (identificador, cantidad, decomiso, municion, foto)
     */
    public static function transformedAttribute($index)
    {
        $attributes = [
            'id' => 'identificador',
            'amount' => 'cantidad',
            'confiscation_id' => 'decomiso',
            'ammunition_id' => 'municion',
            'photo' => 'foto',
        ];

        return isset($attributes[$index]) ? $attributes[$index] : null;
    }
}
