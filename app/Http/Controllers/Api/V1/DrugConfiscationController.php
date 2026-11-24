<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Shared\PersistUploadedImageAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DrugConfiscation\GraphDrugConfiscationRequest;
use App\Http\Requests\Api\V1\DrugConfiscation\IndexDrugConfiscationRequest;
use App\Http\Requests\Api\V1\DrugConfiscation\StoreDrugConfiscationRequest;
use App\Http\Requests\Api\V1\DrugConfiscation\UpdateDrugConfiscationRequest;
use App\Http\Resources\Api\V1\DrugConfiscationResource;
use App\Models\DrugConfiscation;
use App\Support\Cache\ConfiscationReadCache;
use App\Support\Query\ConfiscationStatisticsQuery;
use App\Support\Query\Definitions\DrugConfiscationIndexDefinition;
use App\Support\Query\IndexQueryApplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DrugConfiscationController extends Controller implements HasMiddleware
{
    /**
     * Permisos para items de decomiso de droga y estadísticas.
     *
     * @return array<Middleware> Middlewares registrados.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('can:drugConfiscation.view', only: ['index', 'show', 'indexDeleted', 'indexByConfiscation', 'photo']),
            new Middleware('can:drugConfiscation.create', only: ['store']),
            new Middleware('can:drugConfiscation.update', only: ['update', 'restore']),
            new Middleware('can:drugConfiscation.delete', only: ['destroy']),
            new Middleware('can:graph.view', only: ['graphIndex']),
        ];
    }

    /**
     * Lista registros con filtros y paginación SQL.
     *
     * @return AnonymousResourceCollection Página de recursos con metadatos.
     */
    public function index(IndexDrugConfiscationRequest $request, IndexQueryApplier $index): AnonymousResourceCollection
    {
        $drugConfiscations = DrugConfiscation::query();

        $items = $index->apply($drugConfiscations, $request->validated(), DrugConfiscationIndexDefinition::definition());

        return DrugConfiscationResource::collection($items)->withQuery($request->paginationQuery())->additional([
            'success' => true,
            'message' => 'Drug Confiscation records retrieved successfully.',
        ]);
    }

    /**
     * Lista registros filtrados por decomiso padre.
     *
     * @param  int  $idConfiscation  ID del decomiso.
     * @return AnonymousResourceCollection Colección filtrada.
     */
    public function indexByConfiscation(IndexDrugConfiscationRequest $request, int $idConfiscation, IndexQueryApplier $index): AnonymousResourceCollection
    {
        $drugConfiscations = DrugConfiscation::where('confiscation_id', $idConfiscation);

        $items = $index->apply($drugConfiscations, $request->validated(), DrugConfiscationIndexDefinition::definition());

        return DrugConfiscationResource::collection($items)->withQuery($request->paginationQuery())->additional([
            'success' => true,
            'message' => 'Drug Confiscation records retrieved successfully.',
        ]);
    }

    /**
     * Crea un registro de droga decomisada.
     *
     * @param  StoreDrugConfiscationRequest  $request  Datos validados.
     * @return JsonResponse Recurso creado.
     */
    public function store(StoreDrugConfiscationRequest $request, PersistUploadedImageAction $images): JsonResponse
    {
        $validated = $request->validated();
        $drugConfiscation = $images->execute(new DrugConfiscation, $validated, 'photo', 'drugConfiscation', 'evidence');

        return (new DrugConfiscationResource($drugConfiscation->load(DrugConfiscationIndexDefinition::definition()['with'])))->additional([
            'success' => true,
            'message' => 'Drug Confiscation created successfully.',
        ])->response()->setStatusCode(201);
    }

    /**
     * Muestra un registro específico.
     *
     * @param  DrugConfiscation  $drugConfiscation  Instancia objetivo.
     * @return DrugConfiscationResource Recurso solicitado.
     */
    public function show(DrugConfiscation $drugConfiscation): DrugConfiscationResource
    {
        return (new DrugConfiscationResource($drugConfiscation->load(DrugConfiscationIndexDefinition::definition()['with'])))->additional([
            'success' => true,
            'message' => 'Drug Confiscation retrieved successfully.',
        ]);
    }

    public function photo(DrugConfiscation $drugConfiscation): StreamedResponse
    {
        $path = $drugConfiscation->photo;
        abort_unless(is_string($path) && preg_match('~^drugConfiscation/[A-Za-z0-9_-]+\\.png$~D', $path), 404);
        $disk = Storage::disk('evidence');
        abort_unless($disk->exists($path), 404);

        return $disk->download($path, 'evidence.png', [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Actualiza un registro de droga decomisada.
     *
     * @param  UpdateDrugConfiscationRequest  $request  Datos validados.
     * @param  DrugConfiscation  $drugConfiscation  Recurso objetivo.
     * @return DrugConfiscationResource Recurso actualizado.
     */
    public function update(UpdateDrugConfiscationRequest $request, DrugConfiscation $drugConfiscation, PersistUploadedImageAction $images): DrugConfiscationResource
    {
        $validated = $request->validated();
        $images->execute($drugConfiscation, $validated, 'photo', 'drugConfiscation', 'evidence');

        return (new DrugConfiscationResource($drugConfiscation->load(DrugConfiscationIndexDefinition::definition()['with'])))->additional([
            'success' => true,
            'message' => 'Drug Confiscation updated successfully.',
        ]);
    }

    /**
     * Soft delete del registro.
     *
     * @param  DrugConfiscation  $drugConfiscation  Recurso a eliminar.
     * @return DrugConfiscationResource Recurso eliminado.
     */
    public function destroy(DrugConfiscation $drugConfiscation): DrugConfiscationResource
    {
        $drugConfiscation->delete();

        return (new DrugConfiscationResource($drugConfiscation->load(DrugConfiscationIndexDefinition::definition()['with'])))->additional([
            'success' => true,
            'message' => 'Drug Confiscation deleted successfully.',
        ]);
    }

    /**
     * Lista registros eliminados con filtros y paginación SQL.
     *
     * @return AnonymousResourceCollection Página de recursos con metadatos.
     */
    public function indexDeleted(IndexDrugConfiscationRequest $request, IndexQueryApplier $index): AnonymousResourceCollection
    {
        $drugConfiscations = DrugConfiscation::onlyTrashed();

        $items = $index->apply($drugConfiscations, $request->validated(), DrugConfiscationIndexDefinition::definition());

        return DrugConfiscationResource::collection($items)->withQuery($request->paginationQuery())->additional([
            'success' => true,
            'message' => 'Drug Confiscation records retrieved successfully.',
        ]);
    }

    /**
     * Restaura un registro eliminado.
     *
     * @param  DrugConfiscation  $drugConfiscation  Recurso a restaurar.
     * @return DrugConfiscationResource Recurso restaurado.
     */
    public function restore(DrugConfiscation $drugConfiscation): DrugConfiscationResource
    {
        $drugConfiscation->restore();

        return (new DrugConfiscationResource($drugConfiscation->load(DrugConfiscationIndexDefinition::definition()['with'])))->additional([
            'success' => true,
            'message' => 'Drug Confiscation restored successfully.',
        ]);
    }

    /**
     * Genera datos agregados para gráficos (línea, barras, pie) según período
     * y rango de fechas, filtrando por conjunto de drogas. Usa una vista SQL.
     *
     * @param  GraphDrugConfiscationRequest  $request  Parámetros: period, start_date, end_date, drugs[].
     * @return JsonResponse lineBarData y pieData formateados.
     */
    public function graphIndex(GraphDrugConfiscationRequest $request, ConfiscationStatisticsQuery $statistics, ConfiscationReadCache $cache): JsonResponse
    {
        return response()->json($cache->remember(
            'graph:drug',
            $request->user('api')->getAuthIdentifier(),
            $request->validated(),
            fn (): array => $statistics->get(
                'drug',
                $request->validated('period'),
                $request->validated('start_date'),
                $request->validated('end_date'),
                $request->validated('drugs'),
            ),
        ));
    }
}
