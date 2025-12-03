<?php

namespace App\Http\Resources\WeaponConfiscation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Collection para recursos WeaponConfiscation.
 * 
 * Envuelve colecciones de WeaponConfiscationResource en formato estándar de Laravel.
 */
class WeaponConfiscationCollection extends ResourceCollection
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
