<?php

namespace App\Http\Resources\Ammunition;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Collection para recursos Ammunition.
 * 
 * Envuelve colecciones de AmmunitionResource en formato estándar de Laravel.
 */
class AmmunitionCollection extends ResourceCollection
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
