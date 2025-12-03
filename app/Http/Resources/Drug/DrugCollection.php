<?php

namespace App\Http\Resources\Drug;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Collection para recursos Drug.
 * 
 * Envuelve una colección de DrugResource en el formato estándar de Laravel.
 * Usa el comportamiento por defecto de ResourceCollection sin personalización adicional.
 */
class DrugCollection extends ResourceCollection
{
    /**
     * Transforma la colección de recursos en un array.
     * 
     * Delega a la implementación padre que envuelve cada Drug en DrugResource.
     * 
     * @param Request $request
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
