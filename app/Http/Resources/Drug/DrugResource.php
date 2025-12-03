<?php

namespace App\Http\Resources\Drug;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para el modelo Drug.
 * 
 * Transforma los atributos del modelo Drug al formato de salida de la API.
 * Convierte nombres de campos de base de datos (inglés) a nombres de API (español).
 * Proporciona mapeo bidireccional mediante originalAttribute() y transformedAttribute().
 */
class DrugResource extends JsonResource
{
    /**
     * Transforma el recurso en un array para respuestas JSON.
     * 
     * Mapeo de salida:
     * - id -> identificador
     * - description -> descripcion
     * - logo -> logo (sin cambio)
     * 
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'identificador' => $this->id,
            'descripcion' => $this->description,
            // 'presentacion' => [
            //     'identificador' => $this->drugPresentation->id,
            //     'descripcion' => $this->drugPresentation->description,
            // ],
            'logo' => $this->logo,
        ];
    }

    /**
     * Convierte un atributo de la API (español) a su nombre en la base de datos (inglés).
     * 
     * Usado por TransformInput middleware para traducir claves de entrada del request.
     * 
     * @param string $index Nombre del atributo en español (identificador, descripcion, logo)
     * @return string|null Nombre del atributo en base de datos (id, description, logo)
     */
    public static function originalAttribute($index)
    {
        $attributes = [
            'identificador' => 'id',
            'descripcion' => 'description',
            //'presentacion' => 'drug_presentation_id',
            'logo' => 'logo',
        ];

        return isset($attributes[$index]) ? $attributes[$index] : null;
    }

    /**
     * Convierte un atributo de base de datos (inglés) a su nombre en la API (español).
     * 
     * Usado por TransformInput middleware para traducir claves de mensajes de validación.
     * 
     * @param string $index Nombre del atributo en base de datos (id, description, logo)
     * @return string|null Nombre del atributo en español (identificador, descripcion, logo)
     */
    public static function transformedAttribute($index)
    {
        $attributes = [
            'id' => 'identificador',
            'description' => 'descripcion',
            //'drug_presentation_id' => 'presentacion',
            'logo' => 'logo',
        ];

        return isset($attributes[$index]) ? $attributes[$index] : null;
    }
}
