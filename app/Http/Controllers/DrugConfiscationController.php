<?php

namespace App\Http\Controllers;

use App\Http\Requests\DrugConfiscation\GetRequest;
use App\Models\DrugConfiscation;
use Illuminate\Http\Request;
use App\Http\Requests\DrugConfiscation\StorePostRequest;
use App\Http\Requests\DrugConfiscation\UpdatePutRequest;
use App\Http\Resources\DrugConfiscation\DrugConfiscationResource;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

use function Psy\debug;

class DrugConfiscationController extends ApiController implements HasMiddleware
{
    /**
     * Middlewares para items de decomiso de droga: transforma entrada según recurso.
     *
     * @return array<Middleware> Middlewares registrados.
     */
    public static function middleware(): array
    {
        return [
            new Middleware("transformInput:" . DrugConfiscationResource::class . "", only: ['store', 'update']),
        ];
    }

    /**
     * Lista todos los registros de droga decomisada.
     *
     * @return \Illuminate\Http\JsonResponse Colección de registros.
     */
    public function index()
    {
        $drugConfiscations = DrugConfiscation::all();
        return $this->showAll($drugConfiscations);
    }

    /**
     * Lista registros filtrados por decomiso padre.
     *
     * @param int $idConfiscation ID del decomiso.
     * @return \Illuminate\Http\JsonResponse Colección filtrada.
     */
    public function indexByConfiscation(int $idConfiscation)
    {
        $drugConfiscations = DrugConfiscation::where('confiscation_id', $idConfiscation);
        return $this->showAll($drugConfiscations->get());
    }

    /**
     * Crea un registro de droga decomisada.
     *
     * @param StorePostRequest $request Datos validados.
     * @return \Illuminate\Http\JsonResponse Recurso creado.
     */
    public function store(StorePostRequest $request)
    {
        $validated = $request->validated();
        $drugConfiscation = DrugConfiscation::create($validated);
        return $this->showOne($drugConfiscation);
    }

    /**
     * Muestra un registro específico.
     *
     * @param DrugConfiscation $drugConfiscation Instancia objetivo.
     * @return \Illuminate\Http\JsonResponse Recurso solicitado.
     */
    public function show(DrugConfiscation $drugConfiscation)
    {
        return $this->showOne($drugConfiscation);
    }

    /**
     * Actualiza un registro de droga decomisada.
     *
     * @param UpdatePutRequest $request Datos validados.
     * @param DrugConfiscation $drugConfiscation Recurso objetivo.
     * @return \Illuminate\Http\JsonResponse Recurso actualizado.
     */
    public function update(UpdatePutRequest $request, DrugConfiscation $drugConfiscation)
    {
        $validated = $request->validated();
        $drugConfiscation->update($validated);
        return $this->showOne($drugConfiscation);
    }

    /**
     * Soft delete del registro.
     *
     * @param DrugConfiscation $drugConfiscation Recurso a eliminar.
     * @return \Illuminate\Http\JsonResponse Recurso eliminado.
     */
    public function destroy(DrugConfiscation $drugConfiscation)
    {
        $drugConfiscation->delete();
        return $this->showOne($drugConfiscation);
    }

    /**
     * Lista registros soft-deleted.
     *
     * @return \Illuminate\Http\JsonResponse Colección en papelera.
     */
    public function indexDeleted()
    {
        $drugConfiscations = DrugConfiscation::onlyTrashed()->get();
        return $this->showAll($drugConfiscations);
    }

    /**
     * Restaura un registro eliminado.
     *
     * @param DrugConfiscation $drugConfiscation Recurso a restaurar.
     * @return \Illuminate\Http\JsonResponse Recurso restaurado.
     */
    public function restore(DrugConfiscation $drugConfiscation)
    {
        $drugConfiscation->restore();
        return $this->showOne($drugConfiscation);
    }

    /**
     * Genera datos agregados para gráficos (línea, barras, pie) según período
     * y rango de fechas, filtrando por conjunto de drogas. Usa vistas/materializada.
     *
     * @param GetRequest $request Parámetros: period, start_date, end_date, drugs[].
     * @return \Illuminate\Http\JsonResponse lineBarData y pieData formateados.
     */
    public function graphIndex(GetRequest $request)
    {
        $period = $request->input('period');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $drugs = json_decode($request->input('drugs') ?? '[]');
        $queryp = $this->queryPeriod($period);

        $query = DB::table('data_graph_drug_confiscation')
            ->whereIn('drug_id', $drugs)
            ->whereBetween('full_date', [$startDate, $endDate]);

        $queryBarLine = $query->clone()->select(
            DB::raw("{$queryp} as period"),
            'drug_description',
            DB::raw('SUM(total_amount) as total_amount'),
            DB::raw('SUM(total_weight) as total_weight')
        )
            ->groupBy('period', 'drug_id')
            ->orderBy('period', 'asc')->get();

        $queryPie = $query->clone()->select(
            'drug_description',
            DB::raw('SUM(total_amount) as total_amount'),
            DB::raw('SUM(total_weight) as total_weight')
        )
            ->groupBy('drug_description')
            ->orderBy('drug_description', 'asc')->get();

        $linedata = $this->FormatCollectionBarLine($queryBarLine, "drugs");
        $pieData = $this->FormatCollectionPie($queryPie, "drugs");

        return response()->json(['lineBarData' => $linedata, 'pieData' => $pieData]);
    }
}
