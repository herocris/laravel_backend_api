<?php

namespace App\Http\Resources\Admin\Permission;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para el modelo Permission (Spatie Permission).
 *
 * Transforma atributos del permiso al formato de API.
 */
class PermissionResource extends JsonResource
{
    /**
     * Transforma el recurso en un array para respuestas JSON.
     *
     * Incluye: identificador, nombre
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'identificador' => $this->id,
            'nombre' => $this->name,
        ];
    }
}
