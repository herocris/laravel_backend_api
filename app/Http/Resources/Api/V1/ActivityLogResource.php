<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para el modelo ActivityLog (Spatie Activity Log).
 *
 * Transforma registros de auditoría al formato de API.
 * Incluye información del evento (created/updated/deleted/restored), usuario causante,
 * descripción en español, y cambios realizados (properties).
 */
class ActivityLogResource extends JsonResource
{
    /**
     * Transforma el recurso en un array para respuestas JSON.
     *
     * Incluye:
     * - identificador: ID del log
     * - tipo_de_evento: Tipo de evento Eloquent (created, updated, deleted, restored)
     * - descripcion: Descripción en español generada por Activitylog trait
     * - id_usuario: ID del usuario que causó el cambio
     * - usuario: Nombre del usuario (log_name) que realizó la acción
     * - cambios: JSON con atributos modificados (properties)
     * - fecha: Timestamp de creación del log
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'identificador' => $this->whenHas('id'),
            'tipo_de_evento' => $this->whenHas('event'),
            'descripcion' => $this->whenHas('description'),
            'id_usuario' => $this->whenHas('causer_id'),
            'usuario' => $this->whenHas('log_name'),
            'fecha' => $this->whenHas('created_at'),
            'cambios' => $this->whenHas('properties'),
        ];
    }
}
