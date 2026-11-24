<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Shared\PersistUploadedImageAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AmmunitionConfiscation\GraphAmmunitionConfiscationRequest;
use App\Http\Requests\Api\V1\AmmunitionConfiscation\IndexAmmunitionConfiscationRequest;
use App\Http\Requests\Api\V1\AmmunitionConfiscation\StoreAmmunitionConfiscationRequest;
use App\Http\Requests\Api\V1\AmmunitionConfiscation\UpdateAmmunitionConfiscationRequest;
use App\Http\Resources\Api\V1\AmmunitionConfiscationResource;
use App\Models\AmmunitionConfiscation;
use App\Support\Cache\ConfiscationReadCache;
use App\Support\Query\ConfiscationStatisticsQuery;
use App\Support\Query\Definitions\AmmunitionConfiscationIndexDefinition;
use App\Support\Query\IndexQueryApplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AmmunitionConfiscationController extends Controller implements HasMiddleware
{
    /**
     * Middlewares para items de decomiso de municiones.
     *
     * @return array<Middleware> Middlewares registrados.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('can:ammunitionConfiscation.view', only: ['index', 'show', 'indexDeleted', 'indexByConfiscation', 'photo']),
            new Middleware('can:ammunitionConfiscation.create', only: ['store']),
            new Middleware('can:ammunitionConfiscation.update', only: ['update', 'restore']),
            new Middleware('can:ammunitionConfiscation.delete', only: ['destroy']),
            new Middleware('can:graph.view', only: ['graphIndex']),
        ];
    }

    /**
     * Lista registros con filtros y paginación SQL.
     *
     * @return AnonymousResourceCollection Página de recursos con metadatos.
     */
    public function index(IndexAmmunitionConfiscationRequest $request, IndexQueryApplier $index): AnonymousResourceCollection
    {
        $ammunitionConfiscations = AmmunitionConfiscation::query();

        $items = $index->apply($ammunitionConfiscations, $request->validated(), AmmunitionConfiscationIndexDefinition::definition());

        return AmmunitionConfiscationResource::collection($items)->withQuery($request->paginationQuery())->additional([
            'success' => true,
            'message' => 'Ammunition Confiscation records retrieved successfully.',
        ]);
    }

    /**
     * Lista registros filtrados por decomiso padre.
     *
     * @param  int  $idConfiscation  ID del decomiso.
     * @return AnonymousResourceCollection Colección filtrada.
     */
    public function indexByConfiscation(IndexAmmunitionConfiscationRequest $request, int $idConfiscation, IndexQueryApplier $index): AnonymousResourceCollection
    {
        $ammunitionConfiscations = AmmunitionConfiscation::where('confiscation_id', $idConfiscation);

        $items = $index->apply($ammunitionConfiscations, $request->validated(), AmmunitionConfiscationIndexDefinition::definition());

        return AmmunitionConfiscationResource::collection($items)->withQuery($request->paginationQuery())->additional([
            'success' => true,
            'message' => 'Ammunition Confiscation records retrieved successfully.',
        ]);
    }

    /**
     * Crea un registro de munición decomisada.
     *
     * @param  StoreAmmunitionConfiscationRequest  $request  Datos validados.
     * @return JsonResponse Recurso creado.
     */
    public function store(StoreAmmunitionConfiscationRequest $request, PersistUploadedImageAction $images): JsonResponse
    {
        $validated = $request->validated();
        $ammunitionConfiscation = $images->execute(new AmmunitionConfiscation, $validated, 'photo', 'ammunitionConfiscation', 'evidence');

        return (new AmmunitionConfiscationResource($ammunitionConfiscation->load(AmmunitionConfiscationIndexDefinition::definition()['with'])))->additional([
            'success' => true,
            'message' => 'Ammunition Confiscation created successfully.',
        ])->response()->setStatusCode(201);
    }

    /**
     * Muestra un registro específico.
     *
     * @param  AmmunitionConfiscation  $ammunitionConfiscation  Instancia objetivo.
     * @return AmmunitionConfiscationResource Recurso solicitado.
     */
    public function show(AmmunitionConfiscation $ammunitionConfiscation): AmmunitionConfiscationResource
    {
        return (new AmmunitionConfiscationResource($ammunitionConfiscation->load(AmmunitionConfiscationIndexDefinition::definition()['with'])))->additional([
            'success' => true,
            'message' => 'Ammunition Confiscation retrieved successfully.',
        ]);
    }

    public function photo(AmmunitionConfiscation $ammunitionConfiscation): StreamedResponse
    {
        $path = $ammunitionConfiscation->photo;
        abort_unless(is_string($path) && preg_match('~^ammunitionConfiscation/[A-Za-z0-9_-]+\\.png$~D', $path), 404);
        $disk = Storage::disk('evidence');
        abort_unless($disk->exists($path), 404);

        return $disk->download($path, 'evidence.png', [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Actualiza un registro de munición decomisada.
     *
     * @param  UpdateAmmunitionConfiscationRequest  $request  Datos validados.
     * @param  AmmunitionConfiscation  $ammunitionConfiscation  Recurso objetivo.
     * @return AmmunitionConfiscationResource Recurso actualizado.
     */
    public function update(UpdateAmmunitionConfiscationRequest $request, AmmunitionConfiscation $ammunitionConfiscation, PersistUploadedImageAction $images): AmmunitionConfiscationResource
    {
        $validated = $request->validated();
        $images->execute($ammunitionConfiscation, $validated, 'photo', 'ammunitionConfiscation', 'evidence');

        return (new AmmunitionConfiscationResource($ammunitionConfiscation->load(AmmunitionConfiscationIndexDefinition::definition()['with'])))->additional([
            'success' => true,
            'message' => 'Ammunition Confiscation updated successfully.',
        ]);
    }

    /**
     * Soft delete del registro.
     *
     * @param  AmmunitionConfiscation  $ammunitionConfiscation  Recurso a eliminar.
     * @return AmmunitionConfiscationResource Recurso eliminado.
     */
    public function destroy(AmmunitionConfiscation $ammunitionConfiscation): AmmunitionConfiscationResource
    {
        $ammunitionConfiscation->delete();

        return (new AmmunitionConfiscationResource($ammunitionConfiscation->load(AmmunitionConfiscationIndexDefinition::definition()['with'])))->additional([
            'success' => true,
            'message' => 'Ammunition Confiscation deleted successfully.',
        ]);
    }

    /**
     * Lista registros eliminados con filtros y paginación SQL.
     *
     * @return AnonymousResourceCollection Página de recursos con metadatos.
     */
    public function indexDeleted(IndexAmmunitionConfiscationRequest $request, IndexQueryApplier $index): AnonymousResourceCollection
    {
        $ammunitionConfiscations = AmmunitionConfiscation::onlyTrashed();

        $items = $index->apply($ammunitionConfiscations, $request->validated(), AmmunitionConfiscationIndexDefinition::definition());

        return AmmunitionConfiscationResource::collection($items)->withQuery($request->paginationQuery())->additional([
            'success' => true,
            'message' => 'Ammunition Confiscation records retrieved successfully.',
        ]);
    }

    /**
     * Restaura un registro eliminado.
     *
     * @param  AmmunitionConfiscation  $ammunitionConfiscation  Recurso a restaurar.
     * @return AmmunitionConfiscationResource Recurso restaurado.
     */
    public function restore(AmmunitionConfiscation $ammunitionConfiscation): AmmunitionConfiscationResource
    {
        $ammunitionConfiscation->restore();

        return (new AmmunitionConfiscationResource($ammunitionConfiscation->load(AmmunitionConfiscationIndexDefinition::definition()['with'])))->additional([
            'success' => true,
            'message' => 'Ammunition Confiscation restored successfully.',
        ]);
    }

    /**
     * Genera datos agregados de municiones decomisadas para gráficos (línea, barra, pie).
     *
     * @param  GraphAmmunitionConfiscationRequest  $request  Parámetros: period, start_date, end_date, ammunitions[].
     * @return JsonResponse lineBarData y pieData.
     */
    public function graphIndex(GraphAmmunitionConfiscationRequest $request, ConfiscationStatisticsQuery $statistics, ConfiscationReadCache $cache): JsonResponse
    {
        return response()->json($cache->remember(
            'graph:ammunition',
            $request->user('api')->getAuthIdentifier(),
            $request->validated(),
            fn (): array => $statistics->get(
                'ammunition',
                $request->validated('period'),
                $request->validated('start_date'),
                $request->validated('end_date'),
                $request->validated('ammunitions'),
            ),
        ));
    }
}
