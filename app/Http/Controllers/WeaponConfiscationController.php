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
    public static function middleware(): array
    {
        return [
            new Middleware("transformInput:" . WeaponConfiscationResource::class . "", only: ['store', 'update']),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $weaponConfiscations = WeaponConfiscation::all();
        return $this->showAll($weaponConfiscations);
    }

    public function indexByConfiscation($idConfiscation)
    {

        $weaponConfiscations = WeaponConfiscation::where('confiscation_id', $idConfiscation);
        return $this->showAll($weaponConfiscations->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePostRequest $request)
    {
        $validated = $request->validated();
        $weaponConfiscation = WeaponConfiscation::create($validated);
        return $this->showOne($weaponConfiscation);
    }

    /**
     * Display the specified resource.
     */
    public function show(WeaponConfiscation $weaponConfiscation)
    {
        return $this->showOne($weaponConfiscation);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePutRequest $request, WeaponConfiscation $weaponConfiscation)
    {
        $validated = $request->validated();
        $weaponConfiscation->update($validated);
        return $this->showOne($weaponConfiscation);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(WeaponConfiscation $weaponConfiscation)
    {
        $weaponConfiscation->delete();
        return $this->showOne($weaponConfiscation);
    }

    public function indexDeleted()
    {
        $weaponConfiscations = WeaponConfiscation::onlyTrashed()->get();
        return $this->showAll($weaponConfiscations);
    }
    /**
     * Restore the specified resource from storage.
     */
    public function restore(WeaponConfiscation $weaponConfiscation)
    {
        $weaponConfiscation->restore();
        return $this->showOne($weaponConfiscation);
    }

    public function graphIndex(GetRequest $request)
    {
        $period = $request->input('period'); // Por defecto: día
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $weapons = json_decode($request->input('weapons') ?? '[]');
        $queryp = $this->queryPeriod($period);

        // Construir la consulta con el período seleccionado
        $query = DB::table('data_graph_weapon_confiscation')
            ->whereIn('weapon_id', $weapons)
            ->whereBetween('full_date', [$startDate, $endDate]);

        $queryBarLine = $query->clone()->select( //clonando query para poder utilizar la misma en otra consulta
            DB::raw("{$queryp} as period"),
            'weapon_description',
            DB::raw('SUM(total_amount) as total_amount'),
            //DB::raw('SUM(total_weight) as total_weight')
        )
            ->groupBy('period', 'weapon_id')
            ->orderBy('period', 'asc')->get();

        $queryPie = $query->clone()->select(
            'weapon_description',
            DB::raw('SUM(total_amount) as total_amount'),
           // DB::raw('SUM(total_weight) as total_weight')
        )
            ->groupBy('weapon_description')
            ->orderBy('weapon_description', 'asc')->get();

        $linedata = $this->FormatCollectionBarLine($queryBarLine, "weapons");
        $pieData = $this->FormatCollectionPie($queryPie, "weapons");


        return response()->json(['lineBarData' => $linedata, 'pieData' => $pieData]);
    }
}
