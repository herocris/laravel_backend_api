<?php

namespace App\Http\Resources\Admin\ActivityLog;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Collection para recursos ActivityLog.
 * 
 * Envuelve colecciones de ActivityLogResource en formato estándar de Laravel.
 */
class ActivityLogCollection extends ResourceCollection
{
    /**
     * Transforma la colección de recursos en un array.
     * 
     * @param Request $request
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
