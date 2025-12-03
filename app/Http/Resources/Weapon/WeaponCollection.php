<?php

namespace App\Http\Resources\Weapon;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Collection para recursos Weapon.
 * 
 * Envuelve colecciones de WeaponResource en formato estándar de Laravel.
 */
class WeaponCollection extends ResourceCollection
{
    /**
     * Transforma la colección de recursos en un array.
     * 
     * @param Request $request
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
