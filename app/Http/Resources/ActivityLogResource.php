<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
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
            'tipo_de_evento' => $this->event,
            'descripcion' => $this->description,
            'id_usuario' => $this->causer_id,
            'usuario' => $this->log_name,
            'cambios' => $this->properties,
            'fecha' => $this->created_at,
        ];
    }
}
