<?php

namespace App\Http\Controllers;

use App\Http\Requests\WeaponConfiscation\GetRequest;
use App\Models\WeaponConfiscation;
use Illuminate\Http\Request;
use App\Http\Requests\WeaponConfiscation\StorePostRequest;
use App\Http\Requests\WeaponConfiscation\UpdatePutRequest;
use App\Http\Resources\WeaponConfiscation\WeaponConfiscationResource;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class WeaponConfiscationController extends ApiController implements HasMiddleware
{
    /**
     * Middlewares para items de decomiso de armas: transforma entrada según recurso.
     *
     * @return array<Middleware> Middlewares registrados.
     */
    public static function middleware(): array
    {
        return [
            new Middleware("transformInput:" . WeaponConfiscationResource::class . "", only: ['store', 'update']),
        ];
    }

    /**
     * Lista todos los registros de armas decomisadas.
     *
     * @return \Illuminate\Http\JsonResponse Colección de registros.
     */
    public function index()
    {
        $weaponConfiscations = WeaponConfiscation::all();
        return $this->showAll($weaponConfiscations);
    }

    /**
     * Lista registros filtrados por ID de decomiso padre.
     *
     * @param int $idConfiscation ID del decomiso.
     * @return \Illuminate\Http\JsonResponse Colección filtrada.
     */
    public function indexByConfiscation(int $idConfiscation)
    {
        $weaponConfiscations = WeaponConfiscation::where('confiscation_id', $idConfiscation);
        return $this->showAll($weaponConfiscations->get());
    }

    /**
     * Crea un registro de arma decomisada.
     *
     * @param StorePostRequest $request Datos validados.
     * @return \Illuminate\Http\JsonResponse Recurso creado.
     */
    public function store(StorePostRequest $request)
    {
        $validated = $request->validated();
        $weaponConfiscation = WeaponConfiscation::create($validated);
        return $this->showOne($weaponConfiscation);
    }

    /**
     * Muestra un registro específico.
     *
     * @param WeaponConfiscation $weaponConfiscation Instancia objetivo.
     * @return \Illuminate\Http\JsonResponse Recurso solicitado.
     */
    public function show(WeaponConfiscation $weaponConfiscation)
    {
        return $this->showOne($weaponConfiscation);
    }

    /**
     * Actualiza un registro de arma decomisada.
     *
     * @param UpdatePutRequest $request Datos validados.
     * @param WeaponConfiscation $weaponConfiscation Recurso objetivo.
     * @return \Illuminate\Http\JsonResponse Recurso actualizado.
     */
    public function update(UpdatePutRequest $request, WeaponConfiscation $weaponConfiscation)
    {
        $validated = $request->validated();
        $weaponConfiscation->update($validated);
        return $this->showOne($weaponConfiscation);
    }

    /**
     * Soft delete del registro.
     *
     * @param WeaponConfiscation $weaponConfiscation Recurso a eliminar.
     * @return \Illuminate\Http\JsonResponse Recurso eliminado.
     */
    public function destroy(WeaponConfiscation $weaponConfiscation)
    {
        $weaponConfiscation->delete();
        return $this->showOne($weaponConfiscation);
    }

    /**
     * Lista registros soft-deleted.
     *
     * @return \Illuminate\Http\JsonResponse Colección en papelera.
     */
    public function indexDeleted()
    {
        $weaponConfiscations = WeaponConfiscation::onlyTrashed()->get();
        return $this->showAll($weaponConfiscations);
    }

    /**
     * Restaura un registro eliminado.
     *
     * @param WeaponConfiscation $weaponConfiscation Recurso a restaurar.
     * @return \Illuminate\Http\JsonResponse Recurso restaurado.
     */
    public function restore(WeaponConfiscation $weaponConfiscation)
    {
        $weaponConfiscation->restore();
        return $this->showOne($weaponConfiscation);
    }

    /**
     * Genera datos agregados de armas decomisadas para gráficos.
     * Usa período (día, mes, etc.) y rango de fechas; filtra por conjunto de armas.
     *
     * @param GetRequest $request Parámetros: period, start_date, end_date, weapons[].
     * @return \Illuminate\Http\JsonResponse lineBarData y pieData.
     */
    public function graphIndex(GetRequest $request)
    {
        $period = $request->input('period');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $weapons = json_decode($request->input('weapons') ?? '[]');
        $queryp = $this->queryPeriod($period);

        $query = DB::table('data_graph_weapon_confiscation')
            ->whereIn('weapon_id', $weapons)
            ->whereBetween('full_date', [$startDate, $endDate]);

        $queryBarLine = $query->clone()->select(
            DB::raw("{$queryp} as period"),
            'weapon_description',
            DB::raw('SUM(total_amount) as total_amount')
        )
            ->groupBy('period', 'weapon_id')
            ->orderBy('period', 'asc')->get();

        $queryPie = $query->clone()->select(
            'weapon_description',
            DB::raw('SUM(total_amount) as total_amount')
        )
            ->groupBy('weapon_description')
            ->orderBy('weapon_description', 'asc')->get();

        $linedata = $this->FormatCollectionBarLine($queryBarLine, "weapons");
        $pieData = $this->FormatCollectionPie($queryPie, "weapons");

        return response()->json(['lineBarData' => $linedata, 'pieData' => $pieData]);
    }
}
