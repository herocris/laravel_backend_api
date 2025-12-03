<?php

namespace App\Http\Resources\Admin\Role;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Collection para recursos Role.
 * 
 * Envuelve colecciones de RoleResource en formato estándar de Laravel.
 */
class RoleCollection extends ResourceCollection
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
