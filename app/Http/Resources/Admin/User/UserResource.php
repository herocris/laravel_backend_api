<?php

namespace App\Http\Resources\Admin\User;

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
            'identificador' => $this->id,
            'nombre' => $this->name,
            'correo' => $this->email,
            'roles' => $this->roles->pluck('id')->toArray(),
            'permisos' => $this->permissions->pluck('id')->toArray(),
        ];
    }
}
