<?php

namespace App\Http\Controllers;

use App\Http\Requests\AmmunitionConfiscation\GetRequest;
use App\Http\Requests\AmmunitionConfiscation\StorePostRequest;
use App\Http\Requests\AmmunitionConfiscation\UpdatePutRequest;
use App\Http\Resources\AmmunitionConfiscation\AmmunitionConfiscationResource;
use App\Models\AmmunitionConfiscation;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class AmmunitionConfiscationController extends ApiController implements HasMiddleware
{
    /**
     * Middlewares para items de decomiso de municiones.
     *
     * @return array<Middleware> Middlewares registrados.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:ammunitionConfiscation.view,api', only: ['index', 'show', 'indexDeleted', 'indexByConfiscation']),
            new Middleware('permission:ammunitionConfiscation.create,api', only: ['store']),
            new Middleware('permission:ammunitionConfiscation.update,api', only: ['update', 'restore']),
            new Middleware('permission:ammunitionConfiscation.delete,api', only: ['destroy']),
            new Middleware('permission:graph.view,api', only: ['graphIndex']),
            new Middleware('transformInput:'.AmmunitionConfiscationResource::class.'', only: ['store', 'update']),
        ];
    }

    /**
     * Lista todos los registros de municiones decomisadas.
     *
     * @return JsonResponse Colección de registros.
     */
    public function index()
    {
        $ammunitionConfiscations = AmmunitionConfiscation::all();

        return $this->showAll($ammunitionConfiscations);
    }

    /**
     * Lista registros filtrados por decomiso padre.
     *
     * @param  int  $idConfiscation  ID del decomiso.
     * @return JsonResponse Colección filtrada.
     */
    public function indexByConfiscation(int $idConfiscation)
    {
        $ammunitionConfiscations = AmmunitionConfiscation::where('confiscation_id', $idConfiscation);

        return $this->showAll($ammunitionConfiscations->get());
    }

    /**
     * Crea un registro de munición decomisada.
     *
     * @param  StorePostRequest  $request  Datos validados.
     * @return JsonResponse Recurso creado.
     */
    public function store(StorePostRequest $request)
    {
        $validated = $request->validated();
        $ammunitionConfiscation = AmmunitionConfiscation::create($validated);

        return $this->showOne($ammunitionConfiscation);
    }

    /**
     * Muestra un registro específico.
     *
     * @param  AmmunitionConfiscation  $ammunitionConfiscation  Instancia objetivo.
     * @return JsonResponse Recurso solicitado.
     */
    public function show(AmmunitionConfiscation $ammunitionConfiscation)
    {
        return $this->showOne($ammunitionConfiscation);
    }

    /**
     * Actualiza un registro de munición decomisada.
     *
     * @param  UpdatePutRequest  $request  Datos validados.
     * @param  AmmunitionConfiscation  $ammunitionConfiscation  Recurso objetivo.
     * @return JsonResponse Recurso actualizado.
     */
    public function update(UpdatePutRequest $request, AmmunitionConfiscation $ammunitionConfiscation)
    {
        $validated = $request->validated();
        $ammunitionConfiscation->update($validated);

        return $this->showOne($ammunitionConfiscation);
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

        return $this->showOne($ammunitionConfiscation);
    }

    /**
     * Lista registros soft-deleted.
     *
     * @return JsonResponse Colección en papelera.
     */
    public function indexDeleted()
    {
        $ammunitionConfiscations = AmmunitionConfiscation::onlyTrashed()->get();

        return $this->showAll($ammunitionConfiscations);
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

        return $this->showOne($ammunitionConfiscation);
    }

    /**
     * Genera datos agregados de municiones decomisadas para gráficos (línea, barra, pie).
     *
     * @param  GetRequest  $request  Parámetros: period, start_date, end_date, ammunitions[].
     * @return JsonResponse lineBarData y pieData.
     */
    public function graphIndex(GetRequest $request)
    {
        $period = $request->input('period');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $ammunitions = json_decode($request->input('ammunitions') ?? '[]');
        $queryp = $this->queryPeriod($period);

        $query = DB::table('data_graph_ammunition_confiscation')
            ->whereIn('ammunition_id', $ammunitions)
            ->whereBetween('full_date', [$startDate, $endDate]);

        $queryBarLine = $query->clone()->select(
            DB::raw("{$queryp} as period"),
            'ammunition_description',
            DB::raw('SUM(total_amount) as total_amount')
        )
            ->groupBy('period', 'ammunition_id', 'ammunition_description')
            ->orderBy('period', 'asc')->get();

        $queryPie = $query->clone()->select(
            'ammunition_description',
            DB::raw('SUM(total_amount) as total_amount')
        )
            ->groupBy('ammunition_description')
            ->orderBy('ammunition_description', 'asc')->get();

        $linedata = $this->FormatCollectionBarLine($queryBarLine, 'ammunitions');
        $pieData = $this->FormatCollectionPie($queryPie, 'ammunitions');

        return response()->json(['lineBarData' => $linedata, 'pieData' => $pieData]);
    }
}
