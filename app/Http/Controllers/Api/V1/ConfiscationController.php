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
use App\Support\Query\ConfiscationMapQuery;
use App\Support\Query\Definitions\ConfiscationIndexDefinition;
use App\Support\Query\IndexQueryApplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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
     * @return AnonymousResourceCollection Página de recursos con metadatos.
     */
    public function index(IndexConfiscationRequest $request, IndexQueryApplier $index): AnonymousResourceCollection
    {
        $confiscations = Confiscation::query();

        $items = $index->apply($confiscations, $request->validated(), ConfiscationIndexDefinition::definition());

        return ConfiscationResource::collection($items)->withQuery($request->paginationQuery())->additional([
            'success' => true,
            'message' => 'Confiscation records retrieved successfully.',
        ]);
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

        return (new ConfiscationResource($confiscation))->additional([
            'success' => true,
            'message' => 'Confiscation created successfully.',
        ])->response()->setStatusCode(201);
    }

    /**
     * Muestra un decomiso específico.
     *
     * @param  Confiscation  $confiscation  Instancia objetivo.
     * @return ConfiscationResource Recurso solicitado.
     */
    public function show(Confiscation $confiscation): ConfiscationResource
    {
        return (new ConfiscationResource($confiscation))->additional([
            'success' => true,
            'message' => 'Confiscation retrieved successfully.',
        ]);
    }

    /**
     * Actualiza datos del decomiso.
     *
     * @param  UpdateConfiscationRequest  $request  Datos validados.
     * @param  Confiscation  $confiscation  Recurso a actualizar.
     * @return ConfiscationResource Recurso actualizado.
     */
    public function update(UpdateConfiscationRequest $request, Confiscation $confiscation): ConfiscationResource
    {
        $validated = $request->validated();
        $confiscation->update($validated);

        return (new ConfiscationResource($confiscation))->additional([
            'success' => true,
            'message' => 'Confiscation updated successfully.',
        ]);
    }

    /**
     * Soft delete del decomiso.
     *
     * @param  Confiscation  $confiscation  Recurso a eliminar lógicamente.
     * @return ConfiscationResource Recurso eliminado.
     */
    public function destroy(Confiscation $confiscation): ConfiscationResource
    {
        $confiscation->delete();

        return (new ConfiscationResource($confiscation))->additional([
            'success' => true,
            'message' => 'Confiscation deleted successfully.',
        ]);
    }

    /**
     * Lista registros eliminados con filtros y paginación SQL.
     *
     * @return AnonymousResourceCollection Página de recursos con metadatos.
     */
    public function indexDeleted(IndexConfiscationRequest $request, IndexQueryApplier $index): AnonymousResourceCollection
    {
        $confiscations = Confiscation::onlyTrashed();

        $items = $index->apply($confiscations, $request->validated(), ConfiscationIndexDefinition::definition());

        return ConfiscationResource::collection($items)->withQuery($request->paginationQuery())->additional([
            'success' => true,
            'message' => 'Confiscation records retrieved successfully.',
        ]);
    }

    /**
     * Restaura un decomiso eliminado previamente.
     *
     * @param  Confiscation  $confiscation  Recurso a restaurar.
     * @return ConfiscationResource Recurso restaurado.
     */
    public function restore(Confiscation $confiscation): ConfiscationResource
    {
        $confiscation->restore();

        return (new ConfiscationResource($confiscation))->additional([
            'success' => true,
            'message' => 'Confiscation restored successfully.',
        ]);
    }
}
