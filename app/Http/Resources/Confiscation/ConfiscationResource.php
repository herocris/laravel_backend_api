<?php

namespace App\Http\Resources\Confiscation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para el modelo Confiscation.
 * 
 * Transforma atributos del modelo Confiscation (decomiso) al formato de API.
 * Incluye datos de ubicación geográfica (departamento, municipio, coordenadas) y observaciones.
 * Proporciona mapeo bidireccional para transformación de request/validación.
 */
class ConfiscationResource extends JsonResource
{
    /**
     * Transforma el recurso en un array para respuestas JSON.
     * 
     * Mapeo completo:
     * - id -> identificador
     * - date -> fecha
     * - observation -> observacion
     * - direction -> direccion
     * - department -> departamento
     * - municipality -> municipalidad
     * - latitude -> latitud
     * - length -> longitud
     * 
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'identificador' => $this->id,
            'fecha' => $this->date,
            'observacion' => $this->observation,
            'direccion' => $this->direction,
            'departamento' => $this->department,
            'municipalidad' => $this->municipality,
            'latitud' => $this->latitude,
            'longitud' => $this->length,
        ];
    }

    /**
     * Convierte atributo de API (español) a nombre de base de datos (inglés).
     * 
     * @param string $index Nombre en español (identificador, fecha, observacion, direccion, etc.)
     * @return string|null Nombre en base de datos (id, date, observation, direction, etc.)
     */
    public static function originalAttribute($index)
    {
        $attributes = [
            'identificador' => 'id',
            'fecha' => 'date',
            'observacion' => 'observation',
            'direccion' => 'direction',
            'departamento' => 'department',
            'municipalidad' => 'municipality',
            'latitud' => 'latitude',
            'longitud' => 'length',
        ];

        return isset($attributes[$index]) ? $attributes[$index] : null;
    }

    /**
     * Convierte atributo de base de datos (inglés) a nombre de API (español).
     * 
     * @param string $index Nombre en base de datos (id, date, observation, direction, etc.)
     * @return string|null Nombre en español (identificador, fecha, observacion, direccion, etc.)
     */
    public static function transformedAttribute($index)
    {
        $attributes = [
            'id' => 'identificador',
            'date' => 'fecha',
            'observation' => 'observacion',
            'direction' => 'direccion',
            'department' => 'departamento',
            'municipality' => 'municipalidad',
            'latitude' => 'latitud',
            'length' => 'longitud',
        ];

        return isset($attributes[$index]) ? $attributes[$index] : null;
    }
}
