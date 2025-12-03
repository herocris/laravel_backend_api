<?php

namespace App\Http\Resources\DrugPresentation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Collection para recursos DrugPresentation.
 * 
 * Envuelve colecciones de DrugPresentationResource en formato estándar de Laravel.
 */
class DrugPresentationCollection extends ResourceCollection
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
