<?php

namespace App\Http\Resources\Admin\Role;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para el modelo Role (Spatie Permission).
 * 
 * Transforma atributos del rol al formato de API.
 * Incluye array de IDs de permisos asociados al rol.
 * Proporciona mapeo bidireccional para transformación de request/validación.
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
     * @param Request $request
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

    /**
     * Convierte atributo de API (español) a nombre de base de datos (inglés).
     * 
     * @param string $index Nombre en español (identificador, nombre, permisos)
     * @return string|null Nombre en base de datos (id, name, permissions)
     */
    public static function originalAttribute($index)
    {
        $attributes = [
            'identificador' => 'id',
            'nombre' => 'name',
            'permisos' => 'permissions',
        ];

        return isset($attributes[$index]) ? $attributes[$index] : null;
    }

    /**
     * Convierte atributo de base de datos (inglés) a nombre de API (español).
     * 
     * @param string $index Nombre en base de datos (id, name)
     * @return string|null Nombre en español (identificador, nombre)
     */
    public static function transformedAttribute($index)
    {
        $attributes = [
            'id' => 'identificador',
            'name' => 'nombre',
        ];

        return isset($attributes[$index]) ? $attributes[$index] : null;
    }
}
