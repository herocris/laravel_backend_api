<?php

namespace App\Http\Resources\DrugPresentation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class DrugPresentationCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
