<?php

namespace App\Http\Controllers;

use App\Helpers\ConfiscationReadCache;
use App\Helpers\ConfiscationStatisticsQuery;
use App\Helpers\PersistUploadedImage;
use App\Http\Requests\AmmunitionConfiscation\GetRequest;
use App\Http\Requests\AmmunitionConfiscation\StorePostRequest;
use App\Http\Requests\AmmunitionConfiscation\UpdatePutRequest;
use App\Http\Resources\AmmunitionConfiscation\AmmunitionConfiscationResource;
use App\Models\AmmunitionConfiscation;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AmmunitionConfiscationController extends ApiController implements HasMiddleware
{
    private const LIST_FIELDS = [
        'identificador' => 'id',
        'cantidad' => 'amount',
        'foto' => 'photo',
    ];

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
     * @return JsonResponse Página de recursos con metadatos.
     */
    public function index(): JsonResponse
    {
        $ammunitionConfiscations = AmmunitionConfiscation::query()->with(['confiscation:id,observation', 'ammunition:id,description']);

        return $this->showAll($ammunitionConfiscations, AmmunitionConfiscationResource::class, self::LIST_FIELDS);
    }

    /**
     * Lista registros filtrados por decomiso padre.
     *
     * @param  int  $idConfiscation  ID del decomiso.
     * @return JsonResponse Colección filtrada.
     */
    public function indexByConfiscation(int $idConfiscation): JsonResponse
    {
        $ammunitionConfiscations = AmmunitionConfiscation::where('confiscation_id', $idConfiscation)->with(['confiscation:id,observation', 'ammunition:id,description']);

        return $this->showAll($ammunitionConfiscations, AmmunitionConfiscationResource::class, self::LIST_FIELDS);
    }

    /**
     * Crea un registro de munición decomisada.
     *
     * @param  StorePostRequest  $request  Datos validados.
     * @return JsonResponse Recurso creado.
     */
    public function store(StorePostRequest $request, PersistUploadedImage $images)
    {
        $validated = $request->validated();
        $ammunitionConfiscation = $images->save(new AmmunitionConfiscation, $validated, 'photo', 'ammunitionConfiscation', 'evidence');

        return $this->showOne(new AmmunitionConfiscationResource($ammunitionConfiscation));
    }

    /**
     * Muestra un registro específico.
     *
     * @param  AmmunitionConfiscation  $ammunitionConfiscation  Instancia objetivo.
     * @return JsonResponse Recurso solicitado.
     */
    public function show(AmmunitionConfiscation $ammunitionConfiscation)
    {
        return $this->showOne(new AmmunitionConfiscationResource($ammunitionConfiscation));
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
     * @param  UpdatePutRequest  $request  Datos validados.
     * @param  AmmunitionConfiscation  $ammunitionConfiscation  Recurso objetivo.
     * @return JsonResponse Recurso actualizado.
     */
    public function update(UpdatePutRequest $request, AmmunitionConfiscation $ammunitionConfiscation, PersistUploadedImage $images)
    {
        $validated = $request->validated();
        $images->save($ammunitionConfiscation, $validated, 'photo', 'ammunitionConfiscation', 'evidence');

        return $this->showOne(new AmmunitionConfiscationResource($ammunitionConfiscation));
    }

    /**
     * Soft delete del registro.
     *
     * @param  AmmunitionConfiscation  $ammunitionConfiscation  Recurso a eliminar.
     * @return JsonResponse Recurso eliminado.
     */
    public function destroy(AmmunitionConfiscation $ammunitionConfiscation)
    {
        $ammunitionConfiscation->delete();

        return $this->showOne(new AmmunitionConfiscationResource($ammunitionConfiscation));
    }

    /**
     * Lista registros eliminados con filtros y paginación SQL.
     *
     * @return JsonResponse Página de recursos con metadatos.
     */
    public function indexDeleted(): JsonResponse
    {
        $ammunitionConfiscations = AmmunitionConfiscation::onlyTrashed()->with(['confiscation:id,observation', 'ammunition:id,description']);

        return $this->showAll($ammunitionConfiscations, AmmunitionConfiscationResource::class, self::LIST_FIELDS);
    }

    /**
     * Restaura un registro eliminado.
     *
     * @param  AmmunitionConfiscation  $ammunitionConfiscation  Recurso a restaurar.
     * @return JsonResponse Recurso restaurado.
     */
    public function restore(AmmunitionConfiscation $ammunitionConfiscation)
    {
        $ammunitionConfiscation->restore();

        return $this->showOne(new AmmunitionConfiscationResource($ammunitionConfiscation));
    }

    /**
     * Genera datos agregados de municiones decomisadas para gráficos (línea, barra, pie).
     *
     * @param  GetRequest  $request  Parámetros: period, start_date, end_date, ammunitions[].
     * @return JsonResponse lineBarData y pieData.
     */
    public function graphIndex(GetRequest $request, ConfiscationStatisticsQuery $statistics, ConfiscationReadCache $cache): JsonResponse
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
