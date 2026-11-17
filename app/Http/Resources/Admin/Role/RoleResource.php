<?php

namespace App\Http\Resources\Admin\Role;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para el modelo Role (Spatie Permission).
 *
 * Transforma atributos del rol al formato de API.
 * Incluye array de IDs de permisos asociados al rol.
 */
class RoleResource extends JsonResource
{
    /**
     * Transforma el recurso en un array para respuestas JSON.
     *
     * Incluye:
     * - identificador, nombre
     * - permisos: array de IDs de permisos asignados al rol
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'identificador' => $this->id,
            'nombre' => $this->name,
            'permisos' => $this->permissions->pluck('id')->toArray(),
        ];
    }
}
