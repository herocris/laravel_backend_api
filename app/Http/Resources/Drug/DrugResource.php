<?php

namespace App\Http\Resources\Drug;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DrugResource extends JsonResource
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
            // 'presentacion' => [
            //     'identificador' => $this->drugPresentation->id,
            //     'descripcion' => $this->drugPresentation->description,
            // ],
            'logo' => $this->logo,
        ];
    }

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
