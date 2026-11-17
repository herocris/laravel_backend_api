<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ApiController;
use App\Http\Resources\Admin\ActivityLog\ActivityLogResource;
use App\Models\Activitylog;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class LogActivityController extends ApiController implements HasMiddleware
{
    private const LIST_FIELDS = [
        'identificador' => 'id',
        'tipo_de_evento' => 'event',
        'descripcion' => 'description',
        'id_usuario' => 'causer_id',
        'usuario' => 'log_name',
        'fecha' => 'created_at',
    ];

    /**
     * @return array<Middleware>
     */
    public static function middleware(): array
    {
        return [new Middleware('can:activity.view')];
    }

    /**
     * Lista registros con filtros y paginación SQL.
     *
     * @return JsonResponse Página de recursos con metadatos.
     */
    public function __invoke(): JsonResponse
    {
        $activities = Activitylog::query();

        return $this->showAll($activities, ActivityLogResource::class, self::LIST_FIELDS);
    }
}
