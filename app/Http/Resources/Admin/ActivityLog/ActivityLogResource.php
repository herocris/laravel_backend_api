<?php

namespace App\Http\Resources\Admin\ActivityLog;

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
     * @param Request $request
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
