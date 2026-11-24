<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para el modelo User.
 *
 * Transforma atributos del usuario al formato de API.
 * Incluye arrays de IDs de roles y permisos asociados mediante Spatie Permission.
 */
class UserResource extends JsonResource
{
    /**
     * Transforma el recurso en un array para respuestas JSON.
     *
     * Incluye:
     * - identificador, nombre, correo
     * - roles: array de IDs de roles asignados
     * - permisos: array de IDs de permisos directos asignados
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'identificador' => $this->whenHas('id'),
            'nombre' => $this->whenHas('name'),
            'correo' => $this->whenHas('email'),
            'permisos' => $this->whenLoaded('permissions', fn () => $this->permissions->pluck('id')->toArray()),
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('id')->toArray()),
        ];
    }
}
