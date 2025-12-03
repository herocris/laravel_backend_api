<?php

namespace App\Http\Resources\Ammunition;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para el modelo Ammunition.
 * 
 * Transforma atributos del modelo Ammunition al formato de API.
 * Convierte nombres de campos de inglés (BD) a español (API).
 * Proporciona mapeo bidireccional para transformación de request/validación.
 */
class AmmunitionResource extends JsonResource
{
    /**
     * Transforma el recurso en un array para respuestas JSON.
     * 
     * Mapeo: id -> identificador, description -> descripcion, logo -> logo
     * 
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'identificador' => $this->id,
            'descripcion' => $this->description,
            'logo' => $this->logo,
        ];
    }

    /**
     * Convierte atributo de API (español) a nombre de base de datos (inglés).
     * 
     * @param string $index Nombre en español (identificador, descripcion, logo)
     * @return string|null Nombre en base de datos (id, description, logo)
     */
    public static function originalAttribute($index)
    {
        $attributes = [
            'identificador' => 'id',
            'descripcion' => 'description',
            'logo' => 'logo',
        ];

        return isset($attributes[$index]) ? $attributes[$index] : null;
    }

    /**
     * Convierte atributo de base de datos (inglés) a nombre de API (español).
     * 
     * @param string $index Nombre en base de datos (id, description, logo)
     * @return string|null Nombre en español (identificador, descripcion, logo)
     */
    public static function transformedAttribute($index)
    {
        $attributes = [
            'id' => 'identificador',
            'description' => 'descripcion',
            'logo' => 'logo',
        ];

        return isset($attributes[$index]) ? $attributes[$index] : null;
    }
}
