<?php

namespace App\Http\Controllers;

use App\Helpers\ConfiscationStatisticsQuery;
use App\Helpers\PersistUploadedImage;
use App\Http\Requests\DrugConfiscation\GetRequest;
use App\Http\Requests\DrugConfiscation\StorePostRequest;
use App\Http\Requests\DrugConfiscation\UpdatePutRequest;
use App\Http\Resources\DrugConfiscation\DrugConfiscationResource;
use App\Models\DrugConfiscation;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DrugConfiscationController extends ApiController implements HasMiddleware
{
    private const LIST_FIELDS = [
        'identificador' => 'id',
        'cantidad' => 'amount',
        'peso' => 'weight',
        'foto' => 'photo',
    ];

    /**
     * Middlewares para items de decomiso de droga: transforma entrada según recurso.
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
     * @return JsonResponse Página de recursos con metadatos.
     */
    public function index(): JsonResponse
    {
        $drugConfiscations = DrugConfiscation::query()->with(['confiscation:id,observation', 'drug:id,description', 'drugPresentation:id,description']);

        return $this->showAll($drugConfiscations, DrugConfiscationResource::class, self::LIST_FIELDS);
    }

    /**
     * Lista registros filtrados por decomiso padre.
     *
     * @param  int  $idConfiscation  ID del decomiso.
     * @return JsonResponse Colección filtrada.
     */
    public function indexByConfiscation(int $idConfiscation): JsonResponse
    {
        $drugConfiscations = DrugConfiscation::where('confiscation_id', $idConfiscation)->with(['confiscation:id,observation', 'drug:id,description', 'drugPresentation:id,description']);

        return $this->showAll($drugConfiscations, DrugConfiscationResource::class, self::LIST_FIELDS);
    }

    /**
     * Crea un registro de droga decomisada.
     *
     * @param  StorePostRequest  $request  Datos validados.
     * @return JsonResponse Recurso creado.
     */
    public function store(StorePostRequest $request, PersistUploadedImage $images)
    {
        $validated = $request->validated();
        $drugConfiscation = $images->save(new DrugConfiscation, $validated, 'photo', 'drugConfiscation', 'evidence');

        return $this->showOne(new DrugConfiscationResource($drugConfiscation));
    }

    /**
     * Muestra un registro específico.
     *
     * @param  DrugConfiscation  $drugConfiscation  Instancia objetivo.
     * @return JsonResponse Recurso solicitado.
     */
    public function show(DrugConfiscation $drugConfiscation)
    {
        return $this->showOne(new DrugConfiscationResource($drugConfiscation));
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
     * @param  UpdatePutRequest  $request  Datos validados.
     * @param  DrugConfiscation  $drugConfiscation  Recurso objetivo.
     * @return JsonResponse Recurso actualizado.
     */
    public function update(UpdatePutRequest $request, DrugConfiscation $drugConfiscation, PersistUploadedImage $images)
    {
        $validated = $request->validated();
        $images->save($drugConfiscation, $validated, 'photo', 'drugConfiscation', 'evidence');

        return $this->showOne(new DrugConfiscationResource($drugConfiscation));
    }

    /**
     * Soft delete del registro.
     *
     * @param  DrugConfiscation  $drugConfiscation  Recurso a eliminar.
     * @return JsonResponse Recurso eliminado.
     */
    public function destroy(DrugConfiscation $drugConfiscation)
    {
        $drugConfiscation->delete();

        return $this->showOne(new DrugConfiscationResource($drugConfiscation));
    }

    /**
     * Lista registros eliminados con filtros y paginación SQL.
     *
     * @return JsonResponse Página de recursos con metadatos.
     */
    public function indexDeleted(): JsonResponse
    {
        $drugConfiscations = DrugConfiscation::onlyTrashed()->with(['confiscation:id,observation', 'drug:id,description', 'drugPresentation:id,description']);

        return $this->showAll($drugConfiscations, DrugConfiscationResource::class, self::LIST_FIELDS);
    }

    /**
     * Restaura un registro eliminado.
     *
     * @param  DrugConfiscation  $drugConfiscation  Recurso a restaurar.
     * @return JsonResponse Recurso restaurado.
     */
    public function restore(DrugConfiscation $drugConfiscation)
    {
        $drugConfiscation->restore();

        return $this->showOne(new DrugConfiscationResource($drugConfiscation));
    }

    /**
     * Genera datos agregados para gráficos (línea, barras, pie) según período
     * y rango de fechas, filtrando por conjunto de drogas. Usa una vista SQL.
     *
     * @param  GetRequest  $request  Parámetros: period, start_date, end_date, drugs[].
     * @return JsonResponse lineBarData y pieData formateados.
     */
    public function graphIndex(GetRequest $request, ConfiscationStatisticsQuery $statistics): JsonResponse
    {
        return response()->json($statistics->get(
            'drug',
            $request->validated('period'),
            $request->validated('start_date'),
            $request->validated('end_date'),
            $request->validated('drugs'),
        ));
    }
}
