<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ApiController;
use App\Models\Activitylog;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class LogActivityController extends ApiController implements HasMiddleware
{
    /**
     * @return array<Middleware>
     */
    public static function middleware(): array
    {
        return [new Middleware('permission:activity.view,api')];
    }

    /**
     * Controlador invocable que lista todas las entradas del Activity Log.
     * Útil para auditoría rápida. No aplica filtros, orden ni paginación.
     * Para grandes volúmenes se recomienda implementar criterios (fecha,
     * usuario, evento) y paginar para mejorar rendimiento.
     *
     * @return JsonResponse Colección de actividades registradas.
     */
    public function __invoke()
    {
        $activities = Activitylog::all();

        return $this->showAll($activities);
    }
}
