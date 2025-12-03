<?php

namespace App\Http\Controllers;

use App\Http\Requests\AmmunitionConfiscation\GetRequest;
use App\Models\AmmunitionConfiscation;
use Illuminate\Http\Request;
use App\Http\Requests\AmmunitionConfiscation\StorePostRequest;
use App\Http\Requests\AmmunitionConfiscation\UpdatePutRequest;
use App\Http\Resources\AmmunitionConfiscation\AmmunitionConfiscationResource;
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
            new Middleware("transformInput:" . AmmunitionConfiscationResource::class . "", only: ['store', 'update']),
        ];
    }

    /**
     * Lista todos los registros de municiones decomisadas.
     *
     * @return \Illuminate\Http\JsonResponse Colección de registros.
     */
    public function index()
    {
        $ammunitionConfiscations = AmmunitionConfiscation::all();
        return $this->showAll($ammunitionConfiscations);
    }

    /**
     * Lista registros filtrados por decomiso padre.
     *
     * @param int $idConfiscation ID del decomiso.
     * @return \Illuminate\Http\JsonResponse Colección filtrada.
     */
    public function indexByConfiscation(int $idConfiscation)
    {
        $ammunitionConfiscations = AmmunitionConfiscation::where('confiscation_id', $idConfiscation);
        return $this->showAll($ammunitionConfiscations->get());
    }

    /**
     * Crea un registro de munición decomisada.
     *
     * @param StorePostRequest $request Datos validados.
     * @return \Illuminate\Http\JsonResponse Recurso creado.
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
     * @param AmmunitionConfiscation $ammunitionConfiscation Instancia objetivo.
     * @return \Illuminate\Http\JsonResponse Recurso solicitado.
     */
    public function show(AmmunitionConfiscation $ammunitionConfiscation)
    {
        return $this->showOne($ammunitionConfiscation);
    }

    /**
     * Actualiza un registro de munición decomisada.
     *
     * @param UpdatePutRequest $request Datos validados.
     * @param AmmunitionConfiscation $ammunitionConfiscation Recurso objetivo.
     * @return \Illuminate\Http\JsonResponse Recurso actualizado.
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
     * @param AmmunitionConfiscation $ammunitionConfiscation Recurso a eliminar.
     * @return \Illuminate\Http\JsonResponse Recurso eliminado.
     */
    public function destroy(AmmunitionConfiscation $ammunitionConfiscation)
    {
        $ammunitionConfiscation->delete();
        return $this->showOne($ammunitionConfiscation);
    }

    /**
     * Lista registros soft-deleted.
     *
     * @return \Illuminate\Http\JsonResponse Colección en papelera.
     */
    public function indexDeleted()
    {
        $ammunitionConfiscations = AmmunitionConfiscation::onlyTrashed()->get();
        return $this->showAll($ammunitionConfiscations);
    }

    /**
     * Restaura un registro eliminado.
     *
     * @param AmmunitionConfiscation $ammunitionConfiscation Recurso a restaurar.
     * @return \Illuminate\Http\JsonResponse Recurso restaurado.
     */
    public function restore(AmmunitionConfiscation $ammunitionConfiscation)
    {
        $ammunitionConfiscation->restore();
        return $this->showOne($ammunitionConfiscation);
    }

    /**
     * Genera datos agregados de municiones decomisadas para gráficos (línea, barra, pie).
     *
     * @param GetRequest $request Parámetros: period, start_date, end_date, ammunitions[].
     * @return \Illuminate\Http\JsonResponse lineBarData y pieData.
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
            ->groupBy('period', 'ammunition_id')
            ->orderBy('period', 'asc')->get();

        $queryPie = $query->clone()->select(
            'ammunition_description',
            DB::raw('SUM(total_amount) as total_amount')
        )
            ->groupBy('ammunition_description')
            ->orderBy('ammunition_description', 'asc')->get();

        $linedata = $this->FormatCollectionBarLine($queryBarLine, "ammunitions");
        $pieData = $this->FormatCollectionPie($queryPie, "ammunitions");

        return response()->json(['lineBarData' => $linedata, 'pieData' => $pieData]);
    }
}
