<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Shared\PersistUploadedImageAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\WeaponConfiscation\GraphWeaponConfiscationRequest;
use App\Http\Requests\Api\V1\WeaponConfiscation\IndexWeaponConfiscationRequest;
use App\Http\Requests\Api\V1\WeaponConfiscation\StoreWeaponConfiscationRequest;
use App\Http\Requests\Api\V1\WeaponConfiscation\UpdateWeaponConfiscationRequest;
use App\Http\Resources\Api\V1\WeaponConfiscationResource;
use App\Models\WeaponConfiscation;
use App\Support\Cache\ConfiscationReadCache;
use App\Support\Query\ConfiscationStatisticsQuery;
use App\Support\Query\Definitions\WeaponConfiscationIndexDefinition;
use App\Support\Query\IndexQueryApplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WeaponConfiscationController extends Controller implements HasMiddleware
{
    /**
     * Middlewares para items de decomiso de armas: transforma entrada según recurso.
     *
     * @return array<Middleware> Middlewares registrados.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('can:weaponConfiscation.view', only: ['index', 'show', 'indexDeleted', 'indexByConfiscation', 'photo']),
            new Middleware('can:weaponConfiscation.create', only: ['store']),
            new Middleware('can:weaponConfiscation.update', only: ['update', 'restore']),
            new Middleware('can:weaponConfiscation.delete', only: ['destroy']),
            new Middleware('can:graph.view', only: ['graphIndex']),
        ];
    }

    /**
     * Lista registros con filtros y paginación SQL.
     *
     * @return AnonymousResourceCollection Página de recursos con metadatos.
     */
    public function index(IndexWeaponConfiscationRequest $request, IndexQueryApplier $index): AnonymousResourceCollection
    {
        $weaponConfiscations = WeaponConfiscation::query();

        $items = $index->apply($weaponConfiscations, $request->validated(), WeaponConfiscationIndexDefinition::definition());

        return WeaponConfiscationResource::collection($items)->withQuery($request->paginationQuery())->additional([
            'success' => true,
            'message' => 'Weapon Confiscation records retrieved successfully.',
        ]);
    }

    /**
     * Lista registros filtrados por ID de decomiso padre.
     *
     * @param  int  $idConfiscation  ID del decomiso.
     * @return AnonymousResourceCollection Colección filtrada.
     */
    public function indexByConfiscation(IndexWeaponConfiscationRequest $request, int $idConfiscation, IndexQueryApplier $index): AnonymousResourceCollection
    {
        $weaponConfiscations = WeaponConfiscation::where('confiscation_id', $idConfiscation);

        $items = $index->apply($weaponConfiscations, $request->validated(), WeaponConfiscationIndexDefinition::definition());

        return WeaponConfiscationResource::collection($items)->withQuery($request->paginationQuery())->additional([
            'success' => true,
            'message' => 'Weapon Confiscation records retrieved successfully.',
        ]);
    }

    /**
     * Crea un registro de arma decomisada.
     *
     * @param  StoreWeaponConfiscationRequest  $request  Datos validados.
     * @return JsonResponse Recurso creado.
     */
    public function store(StoreWeaponConfiscationRequest $request, PersistUploadedImageAction $images): JsonResponse
    {
        $validated = $request->validated();
        $weaponConfiscation = $images->execute(new WeaponConfiscation, $validated, 'photo', 'weaponConfiscation', 'evidence');

        return (new WeaponConfiscationResource($weaponConfiscation->load(WeaponConfiscationIndexDefinition::definition()['with'])))->additional([
            'success' => true,
            'message' => 'Weapon Confiscation created successfully.',
        ])->response()->setStatusCode(201);
    }

    /**
     * Muestra un registro específico.
     *
     * @param  WeaponConfiscation  $weaponConfiscation  Instancia objetivo.
     * @return WeaponConfiscationResource Recurso solicitado.
     */
    public function show(WeaponConfiscation $weaponConfiscation): WeaponConfiscationResource
    {
        return (new WeaponConfiscationResource($weaponConfiscation->load(WeaponConfiscationIndexDefinition::definition()['with'])))->additional([
            'success' => true,
            'message' => 'Weapon Confiscation retrieved successfully.',
        ]);
    }

    public function photo(WeaponConfiscation $weaponConfiscation): StreamedResponse
    {
        $path = $weaponConfiscation->photo;
        abort_unless(is_string($path) && preg_match('~^weaponConfiscation/[A-Za-z0-9_-]+\\.png$~D', $path), 404);
        $disk = Storage::disk('evidence');
        abort_unless($disk->exists($path), 404);

        return $disk->download($path, 'evidence.png', [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Actualiza un registro de arma decomisada.
     *
     * @param  UpdateWeaponConfiscationRequest  $request  Datos validados.
     * @param  WeaponConfiscation  $weaponConfiscation  Recurso objetivo.
     * @return WeaponConfiscationResource Recurso actualizado.
     */
    public function update(UpdateWeaponConfiscationRequest $request, WeaponConfiscation $weaponConfiscation, PersistUploadedImageAction $images): WeaponConfiscationResource
    {
        $validated = $request->validated();
        $images->execute($weaponConfiscation, $validated, 'photo', 'weaponConfiscation', 'evidence');

        return (new WeaponConfiscationResource($weaponConfiscation->load(WeaponConfiscationIndexDefinition::definition()['with'])))->additional([
            'success' => true,
            'message' => 'Weapon Confiscation updated successfully.',
        ]);
    }

    /**
     * Soft delete del registro.
     *
     * @param  WeaponConfiscation  $weaponConfiscation  Recurso a eliminar.
     * @return WeaponConfiscationResource Recurso eliminado.
     */
    public function destroy(WeaponConfiscation $weaponConfiscation): WeaponConfiscationResource
    {
        $weaponConfiscation->delete();

        return (new WeaponConfiscationResource($weaponConfiscation->load(WeaponConfiscationIndexDefinition::definition()['with'])))->additional([
            'success' => true,
            'message' => 'Weapon Confiscation deleted successfully.',
        ]);
    }

    /**
     * Lista registros eliminados con filtros y paginación SQL.
     *
     * @return AnonymousResourceCollection Página de recursos con metadatos.
     */
    public function indexDeleted(IndexWeaponConfiscationRequest $request, IndexQueryApplier $index): AnonymousResourceCollection
    {
        $weaponConfiscations = WeaponConfiscation::onlyTrashed();

        $items = $index->apply($weaponConfiscations, $request->validated(), WeaponConfiscationIndexDefinition::definition());

        return WeaponConfiscationResource::collection($items)->withQuery($request->paginationQuery())->additional([
            'success' => true,
            'message' => 'Weapon Confiscation records retrieved successfully.',
        ]);
    }

    /**
     * Restaura un registro eliminado.
     *
     * @param  WeaponConfiscation  $weaponConfiscation  Recurso a restaurar.
     * @return WeaponConfiscationResource Recurso restaurado.
     */
    public function restore(WeaponConfiscation $weaponConfiscation): WeaponConfiscationResource
    {
        $weaponConfiscation->restore();

        return (new WeaponConfiscationResource($weaponConfiscation->load(WeaponConfiscationIndexDefinition::definition()['with'])))->additional([
            'success' => true,
            'message' => 'Weapon Confiscation restored successfully.',
        ]);
    }

    /**
     * Genera datos agregados de armas decomisadas para gráficos.
     * Usa períodos de calendario y rango de fechas; filtra por conjunto de armas.
     *
     * @param  GraphWeaponConfiscationRequest  $request  Parámetros: period, start_date, end_date, weapons[].
     * @return JsonResponse lineBarData y pieData.
     */
    public function graphIndex(GraphWeaponConfiscationRequest $request, ConfiscationStatisticsQuery $statistics, ConfiscationReadCache $cache): JsonResponse
    {
        return response()->json($cache->remember(
            'graph:weapon',
            $request->user('api')->getAuthIdentifier(),
            $request->validated(),
            fn (): array => $statistics->get(
                'weapon',
                $request->validated('period'),
                $request->validated('start_date'),
                $request->validated('end_date'),
                $request->validated('weapons'),
            ),
        ));
    }
}
