<?php

namespace App\Http\Controllers;

use App\Helpers\ConfiscationStatisticsQuery;
use App\Helpers\PersistUploadedImage;
use App\Http\Requests\WeaponConfiscation\GetRequest;
use App\Http\Requests\WeaponConfiscation\StorePostRequest;
use App\Http\Requests\WeaponConfiscation\UpdatePutRequest;
use App\Http\Resources\WeaponConfiscation\WeaponConfiscationResource;
use App\Models\WeaponConfiscation;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WeaponConfiscationController extends ApiController implements HasMiddleware
{
    private const LIST_FIELDS = [
        'identificador' => 'id',
        'cantidad' => 'amount',
        'foto' => 'photo',
    ];

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
     * @return JsonResponse Página de recursos con metadatos.
     */
    public function index(): JsonResponse
    {
        $weaponConfiscations = WeaponConfiscation::query()->with(['confiscation:id,observation', 'weapon:id,description']);

        return $this->showAll($weaponConfiscations, WeaponConfiscationResource::class, self::LIST_FIELDS);
    }

    /**
     * Lista registros filtrados por ID de decomiso padre.
     *
     * @param  int  $idConfiscation  ID del decomiso.
     * @return JsonResponse Colección filtrada.
     */
    public function indexByConfiscation(int $idConfiscation): JsonResponse
    {
        $weaponConfiscations = WeaponConfiscation::where('confiscation_id', $idConfiscation)->with(['confiscation:id,observation', 'weapon:id,description']);

        return $this->showAll($weaponConfiscations, WeaponConfiscationResource::class, self::LIST_FIELDS);
    }

    /**
     * Crea un registro de arma decomisada.
     *
     * @param  StorePostRequest  $request  Datos validados.
     * @return JsonResponse Recurso creado.
     */
    public function store(StorePostRequest $request, PersistUploadedImage $images)
    {
        $validated = $request->validated();
        $weaponConfiscation = $images->save(new WeaponConfiscation, $validated, 'photo', 'weaponConfiscation', 'evidence');

        return $this->showOne(new WeaponConfiscationResource($weaponConfiscation));
    }

    /**
     * Muestra un registro específico.
     *
     * @param  WeaponConfiscation  $weaponConfiscation  Instancia objetivo.
     * @return JsonResponse Recurso solicitado.
     */
    public function show(WeaponConfiscation $weaponConfiscation)
    {
        return $this->showOne(new WeaponConfiscationResource($weaponConfiscation));
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
     * @param  UpdatePutRequest  $request  Datos validados.
     * @param  WeaponConfiscation  $weaponConfiscation  Recurso objetivo.
     * @return JsonResponse Recurso actualizado.
     */
    public function update(UpdatePutRequest $request, WeaponConfiscation $weaponConfiscation, PersistUploadedImage $images)
    {
        $validated = $request->validated();
        $images->save($weaponConfiscation, $validated, 'photo', 'weaponConfiscation', 'evidence');

        return $this->showOne(new WeaponConfiscationResource($weaponConfiscation));
    }

    /**
     * Soft delete del registro.
     *
     * @param  WeaponConfiscation  $weaponConfiscation  Recurso a eliminar.
     * @return JsonResponse Recurso eliminado.
     */
    public function destroy(WeaponConfiscation $weaponConfiscation)
    {
        $weaponConfiscation->delete();

        return $this->showOne(new WeaponConfiscationResource($weaponConfiscation));
    }

    /**
     * Lista registros eliminados con filtros y paginación SQL.
     *
     * @return JsonResponse Página de recursos con metadatos.
     */
    public function indexDeleted(): JsonResponse
    {
        $weaponConfiscations = WeaponConfiscation::onlyTrashed()->with(['confiscation:id,observation', 'weapon:id,description']);

        return $this->showAll($weaponConfiscations, WeaponConfiscationResource::class, self::LIST_FIELDS);
    }

    /**
     * Restaura un registro eliminado.
     *
     * @param  WeaponConfiscation  $weaponConfiscation  Recurso a restaurar.
     * @return JsonResponse Recurso restaurado.
     */
    public function restore(WeaponConfiscation $weaponConfiscation)
    {
        $weaponConfiscation->restore();

        return $this->showOne(new WeaponConfiscationResource($weaponConfiscation));
    }

    /**
     * Genera datos agregados de armas decomisadas para gráficos.
     * Usa períodos de calendario y rango de fechas; filtra por conjunto de armas.
     *
     * @param  GetRequest  $request  Parámetros: period, start_date, end_date, weapons[].
     * @return JsonResponse lineBarData y pieData.
     */
    public function graphIndex(GetRequest $request, ConfiscationStatisticsQuery $statistics): JsonResponse
    {
        return response()->json($statistics->get(
            'weapon',
            $request->validated('period'),
            $request->validated('start_date'),
            $request->validated('end_date'),
            $request->validated('weapons'),
        ));
    }
}
