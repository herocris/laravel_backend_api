<?php

namespace App\Http\Controllers;

use App\Helpers\ConfiscationMapQuery;
use App\Http\Requests\Confiscation\GetRequest;
use App\Http\Requests\Confiscation\StorePostRequest;
use App\Http\Requests\Confiscation\UpdatePutRequest;
use App\Http\Resources\Confiscation\ConfiscationResource;
use App\Models\Confiscation;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ConfiscationController extends ApiController implements HasMiddleware
{
    private const LIST_FIELDS = [
        'identificador' => 'id',
        'fecha' => 'date',
        'observacion' => 'observation',
        'direccion' => 'direction',
        'departamento' => 'department',
        'municipalidad' => 'municipality',
        'latitud' => 'latitude',
        'longitud' => 'length',
    ];

    /**
     * Middlewares para el controlador de decomisos.
     *
     * @return array<Middleware> Lista de middlewares activos.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('can:confiscation.view', only: ['index', 'show', 'indexDeleted']),
            new Middleware('can:confiscation.create', only: ['store']),
            new Middleware('can:confiscation.update', only: ['update', 'restore']),
            new Middleware('can:confiscation.delete', only: ['destroy']),
            new Middleware('can:map.view', only: ['mapConfiscations']),
        ];
    }

    /**
     * Lista registros con filtros y paginación SQL.
     *
     * @return JsonResponse Página de recursos con metadatos.
     */
    public function index(): JsonResponse
    {
        $confiscations = Confiscation::query();

        return $this->showAll($confiscations, ConfiscationResource::class, self::LIST_FIELDS);
    }

    /**
     * Construye respuesta de mapa con decomisos y sus ítems filtrados por rango
     * de fechas y subconjuntos de droga/arma/munición. Optimiza la carga usando
     * relaciones condicionales y `whereIn`. Filtra decomisos sin contenido útil.
     *
     * @param  GetRequest  $request  Parámetros: start_date, end_date, drugs[], weapons[], ammunitions[].
     * @return JsonResponse Arreglo 'mapItems' listo para representación en mapa.
     */
    public function mapConfiscations(GetRequest $request, ConfiscationMapQuery $map): JsonResponse
    {
        return response()->json(['mapItems' => $map->get(
            $request->validated('start_date'),
            $request->validated('end_date'),
            $request->validated('drugs', []),
            $request->validated('weapons', []),
            $request->validated('ammunitions', []),
        )]);
    }

    /**
     * Crea un nuevo decomiso con datos validados.
     *
     * @param  StorePostRequest  $request  Datos validados (fecha, coordenadas, etc.).
     * @return JsonResponse Recurso creado.
     */
    public function store(StorePostRequest $request)
    {
        $validated = $request->validated();
        $confiscation = Confiscation::create($validated);

        return $this->showOne(new ConfiscationResource($confiscation));
    }

    /**
     * Muestra un decomiso específico.
     *
     * @param  Confiscation  $confiscation  Instancia objetivo.
     * @return JsonResponse Recurso solicitado.
     */
    public function show(Confiscation $confiscation)
    {
        return $this->showOne(new ConfiscationResource($confiscation));
    }

    /**
     * Actualiza datos del decomiso.
     *
     * @param  UpdatePutRequest  $request  Datos validados.
     * @param  Confiscation  $confiscation  Recurso a actualizar.
     * @return JsonResponse Recurso actualizado.
     */
    public function update(UpdatePutRequest $request, Confiscation $confiscation)
    {
        $validated = $request->validated();
        $confiscation->update($validated);

        return $this->showOne(new ConfiscationResource($confiscation));
    }

    /**
     * Soft delete del decomiso.
     *
     * @param  Confiscation  $confiscation  Recurso a eliminar lógicamente.
     * @return JsonResponse Recurso eliminado.
     */
    public function destroy(Confiscation $confiscation)
    {
        $confiscation->delete();

        return $this->showOne(new ConfiscationResource($confiscation));
    }

    /**
     * Lista registros eliminados con filtros y paginación SQL.
     *
     * @return JsonResponse Página de recursos con metadatos.
     */
    public function indexDeleted(): JsonResponse
    {
        $confiscations = Confiscation::onlyTrashed();

        return $this->showAll($confiscations, ConfiscationResource::class, self::LIST_FIELDS);
    }

    /**
     * Restaura un decomiso eliminado previamente.
     *
     * @param  Confiscation  $confiscation  Recurso a restaurar.
     * @return JsonResponse Recurso restaurado.
     */
    public function restore(Confiscation $confiscation)
    {
        $confiscation->restore();

        return $this->showOne(new ConfiscationResource($confiscation));
    }
}
