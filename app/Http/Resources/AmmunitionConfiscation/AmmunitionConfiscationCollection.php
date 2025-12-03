<?php

namespace App\Http\Resources\AmmunitionConfiscation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Collection para recursos AmmunitionConfiscation.
 * 
 * Envuelve colecciones de AmmunitionConfiscationResource en formato estándar de Laravel.
 */
class AmmunitionConfiscationCollection extends ResourceCollection
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
