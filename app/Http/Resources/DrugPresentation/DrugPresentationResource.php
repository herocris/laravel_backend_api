<?php

namespace App\Http\Resources\DrugPresentation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DrugPresentationResource extends JsonResource
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
            'descripcion' => $this->description,
            'logo' => $this->logo,
        ];
    }

    public static function originalAttribute($index)
    {
        $attributes = [
            'identificador' => 'id',
            'descripcion' => 'description',
            'logo' => 'logo',
        ];

        return isset($attributes[$index]) ? $attributes[$index] : null;
    }

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
