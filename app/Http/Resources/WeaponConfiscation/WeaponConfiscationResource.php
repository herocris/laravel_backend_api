<?php

namespace App\Http\Resources\WeaponConfiscation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para el modelo WeaponConfiscation.
 * 
 * Transforma atributos del modelo WeaponConfiscation al formato de API.
 * Incluye relaciones anidadas con Confiscation (decomiso) y Weapon (arma).
 * Proporciona mapeo bidireccional incluyendo IDs de relaciones.
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
            'arma' => [
                'identificador' => $this->weapon->id,
                'descripcion' => $this->weapon->description,
            ],
            'foto' => $this->photo,
        ];
    }

    /**
     * Convierte atributo de API (español) a nombre de base de datos (inglés).
     * 
     * Incluye mapeo de claves de relaciones (decomiso, arma) a foreign keys.
     * 
     * @param string $index Nombre en español (identificador, cantidad, decomiso, arma, foto)
     * @return string|null Nombre en base de datos (id, amount, confiscation_id, weapon_id, photo)
     */
    public static function originalAttribute($index)
    {
        $attributes = [
            'identificador' => 'id',
            'cantidad' => 'amount',
            'decomiso' => 'confiscation_id',
            'arma' => 'weapon_id',
            'foto' => 'photo',
        ];

        return isset($attributes[$index]) ? $attributes[$index] : null;
    }

    /**
     * Convierte atributo de base de datos (inglés) a nombre de API (español).
     * 
     * Incluye mapeo de foreign keys a claves de relaciones en español.
     * 
     * @param string $index Nombre en base de datos (id, amount, confiscation_id, weapon_id, photo)
     * @return string|null Nombre en español (identificador, cantidad, decomiso, arma, foto)
     */
    public static function transformedAttribute($index)
    {
        $attributes = [
            'id' => 'identificador',
            'amount' => 'cantidad',
            'confiscation_id' => 'decomiso',
            'weapon_id' => 'arma',
            'photo' => 'foto',
        ];

        return isset($attributes[$index]) ? $attributes[$index] : null;
    }
}
