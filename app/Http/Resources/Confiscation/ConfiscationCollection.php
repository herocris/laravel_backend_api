<?php

namespace App\Http\Resources\Confiscation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Collection para recursos Confiscation.
 * 
 * Envuelve colecciones de ConfiscationResource en formato estándar de Laravel.
 */
class ConfiscationCollection extends ResourceCollection
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
