<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ApiController;
use App\Models\Activitylog;
use Illuminate\Http\Request;


class LogActivityController extends ApiController
{
    /**
     * Controlador invocable que lista todas las entradas del Activity Log.
     * Útil para auditoría rápida. No aplica filtros, orden ni paginación.
     * Para grandes volúmenes se recomienda implementar criterios (fecha,
     * usuario, evento) y paginar para mejorar rendimiento.
     *
     * @return \Illuminate\Http\JsonResponse Colección de actividades registradas.
     */
    public function __invoke()
    {
        $activities = Activitylog::all();
        return $this->showAll($activities);
    }
}
