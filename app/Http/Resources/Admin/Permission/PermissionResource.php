<?php

namespace App\Http\Resources\Admin\Permission;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para el modelo Permission (Spatie Permission).
 * 
 * Transforma atributos del permiso al formato de API.
 * Proporciona mapeo bidireccional simple para identificador y nombre.
 */
class PermissionResource extends JsonResource
{
    /**
     * Transforma el recurso en un array para respuestas JSON.
     * 
     * Incluye: identificador, nombre
     * 
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'identificador' => $this->id,
            'nombre' => $this->name,
        ];
    }

    /**
     * Convierte atributo de API (español) a nombre de base de datos (inglés).
     * 
     * @param string $index Nombre en español (identificador, nombre)
     * @return string|null Nombre en base de datos (id, name)
     */
    public static function originalAttribute($index)
    {
        $attributes = [
            'identificador' => 'id',
            'nombre' => 'name',
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
