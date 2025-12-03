<?php

namespace App\Http\Resources\Admin\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para el modelo User.
 * 
 * Transforma atributos del usuario al formato de API.
 * Incluye arrays de IDs de roles y permisos asociados mediante Spatie Permission.
 * Proporciona mapeo bidireccional para transformación de request/validación.
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
     * @param Request $request
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

    /**
     * Convierte atributo de API (español) a nombre de base de datos (inglés).
     * 
     * Incluye mapeo para roles y permisos (relaciones many-to-many).
     * 
     * @param string $index Nombre en español (identificador, nombre, correo, password, roles, permisos)
     * @return string|null Nombre en base de datos (id, name, email, password, roles, permissions)
     */
    public static function originalAttribute($index)
    {
        $attributes = [
            'identificador' => 'id',
            'nombre' => 'name',
            'correo' => 'email',
            'password' => 'password',
            'roles' => 'roles',
            'permisos' => 'permissions',
        ];

        return isset($attributes[$index]) ? $attributes[$index] : null;
    }

    /**
     * Convierte atributo de base de datos (inglés) a nombre de API (español).
     * 
     * @param string $index Nombre en base de datos (id, name, email, password)
     * @return string|null Nombre en español (identificador, nombre, correo, password)
     */
    public static function transformedAttribute($index)
    {
        $attributes = [
            'id' => 'identificador',
            'name' => 'nombre',
            'email' => 'correo',
            'password' => 'password',
        ];

        return isset($attributes[$index]) ? $attributes[$index] : null;
    }
}
