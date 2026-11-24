<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Confiscation\IndexConfiscationRequest;
use App\Http\Requests\Api\V1\Confiscation\MapConfiscationsRequest;
use App\Http\Requests\Api\V1\Confiscation\StoreConfiscationRequest;
use App\Http\Requests\Api\V1\Confiscation\UpdateConfiscationRequest;
use App\Http\Resources\Api\V1\ConfiscationResource;
use App\Models\Confiscation;
use App\Support\Cache\ConfiscationReadCache;
use App\Support\Http\ApiResponse;
use App\Support\Query\ConfiscationMapQuery;
use App\Support\Query\Definitions\ConfiscationIndexDefinition;
use App\Support\Query\IndexQueryApplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ConfiscationController extends Controller implements HasMiddleware
{
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
    public function index(IndexConfiscationRequest $request, IndexQueryApplier $index): JsonResponse
    {
        $confiscations = Confiscation::query();

        return ApiResponse::paginated($index->apply($confiscations, $request->validated(), ConfiscationIndexDefinition::definition()), ConfiscationResource::class, $request);
    }

    /**
     * Construye respuesta de mapa con decomisos y sus ítems filtrados por rango
     * de fechas y subconjuntos de droga/arma/munición. Optimiza la carga usando
     * relaciones condicionales y `whereIn`. Filtra decomisos sin contenido útil.
     *
     * @param  MapConfiscationsRequest  $request  Parámetros: start_date, end_date, drugs[], weapons[], ammunitions[].
     * @return JsonResponse Arreglo 'mapItems' listo para representación en mapa.
     */
    public function mapConfiscations(MapConfiscationsRequest $request, ConfiscationMapQuery $map, ConfiscationReadCache $cache): JsonResponse
    {
        return response()->json($cache->remember(
            'map',
            $request->user('api')->getAuthIdentifier(),
            $request->validated(),
            fn (): array => ['mapItems' => $map->get(
                $request->validated('start_date'),
                $request->validated('end_date'),
                $request->validated('drugs', []),
                $request->validated('weapons', []),
                $request->validated('ammunitions', []),
            )->toArray()],
        ));
    }

    /**
     * Crea un nuevo decomiso con datos validados.
     *
     * @param  StoreConfiscationRequest  $request  Datos validados (fecha, coordenadas, etc.).
     * @return JsonResponse Recurso creado.
     */
    public function store(StoreConfiscationRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $confiscation = Confiscation::create($validated);

        return ApiResponse::resource(new ConfiscationResource($confiscation));
    }

    /**
     * Muestra un decomiso específico.
     *
     * @param  Confiscation  $confiscation  Instancia objetivo.
     * @return JsonResponse Recurso solicitado.
     */
    public function show(Confiscation $confiscation): JsonResponse
    {
        return ApiResponse::resource(new ConfiscationResource($confiscation));
    }

    /**
     * Actualiza datos del decomiso.
     *
     * @param  UpdateConfiscationRequest  $request  Datos validados.
     * @param  Confiscation  $confiscation  Recurso a actualizar.
     * @return JsonResponse Recurso actualizado.
     */
    public function update(UpdateConfiscationRequest $request, Confiscation $confiscation): JsonResponse
    {
        $validated = $request->validated();
        $confiscation->update($validated);

        return ApiResponse::resource(new ConfiscationResource($confiscation));
    }

    /**
     * Soft delete del decomiso.
     *
     * @param  Confiscation  $confiscation  Recurso a eliminar lógicamente.
     * @return JsonResponse Recurso eliminado.
     */
    public function destroy(Confiscation $confiscation): JsonResponse
    {
        $confiscation->delete();

        return ApiResponse::resource(new ConfiscationResource($confiscation));
    }

    /**
     * Lista registros eliminados con filtros y paginación SQL.
     *
     * @return JsonResponse Página de recursos con metadatos.
     */
    public function indexDeleted(IndexConfiscationRequest $request, IndexQueryApplier $index): JsonResponse
    {
        $confiscations = Confiscation::onlyTrashed();

        return ApiResponse::paginated($index->apply($confiscations, $request->validated(), ConfiscationIndexDefinition::definition()), ConfiscationResource::class, $request);
    }

    /**
     * Restaura un decomiso eliminado previamente.
     *
     * @param  Confiscation  $confiscation  Recurso a restaurar.
     * @return JsonResponse Recurso restaurado.
     */
    public function restore(Confiscation $confiscation): JsonResponse
    {
        $confiscation->restore();

        return ApiResponse::resource(new ConfiscationResource($confiscation));
    }
}
