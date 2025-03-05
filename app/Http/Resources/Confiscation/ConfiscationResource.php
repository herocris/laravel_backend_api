<?php

namespace App\Http\Resources\Confiscation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConfiscationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
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
