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
    public static function middleware(): array
    {
        return [
            new Middleware("transformInput:" . AmmunitionConfiscationResource::class . "", only: ['store', 'update']),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $ammunitionConfiscations = AmmunitionConfiscation::all();
        return $this->showAll($ammunitionConfiscations);
    }

    public function indexByConfiscation($idConfiscation)
    {

        $ammunitionConfiscations = AmmunitionConfiscation::where('confiscation_id', $idConfiscation);
        return $this->showAll($ammunitionConfiscations->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePostRequest $request)
    {
        $validated = $request->validated();
        $ammunitionConfiscation = AmmunitionConfiscation::create($validated);
        return $this->showOne($ammunitionConfiscation);
    }

    /**
     * Display the specified resource.
     */
    public function show(AmmunitionConfiscation $ammunitionConfiscation)
    {
        return $this->showOne($ammunitionConfiscation);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePutRequest $request, AmmunitionConfiscation $ammunitionConfiscation)
    {
        $validated = $request->validated();
        $ammunitionConfiscation->update($validated);
        return $this->showOne($ammunitionConfiscation);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AmmunitionConfiscation $ammunitionConfiscation)
    {
        $ammunitionConfiscation->delete();
        return $this->showOne($ammunitionConfiscation);
    }

    public function indexDeleted()
    {
        $ammunitionConfiscations = AmmunitionConfiscation::onlyTrashed()->get();
        return $this->showAll($ammunitionConfiscations);
    }
    /**
     * Restore the specified resource from storage.
     */
    public function restore(AmmunitionConfiscation $ammunitionConfiscation)
    {
        $ammunitionConfiscation->restore();
        return $this->showOne($ammunitionConfiscation);
    }

    public function graphIndex(GetRequest $request)
    {
        $period = $request->input('period'); // Por defecto: día
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $ammunitions = json_decode($request->input('ammunitions') ?? '[]');
        $queryp = $this->queryPeriod($period);

        // Construir la consulta con el período seleccionado
        $query = DB::table('data_graph_ammunition_confiscation')
            ->whereIn('ammunition_id', $ammunitions)
            ->whereBetween('full_date', [$startDate, $endDate]);

        $queryBarLine = $query->clone()->select( //clonando query para poder utilizar la misma en otra consulta
            DB::raw("{$queryp} as period"),
            'ammunition_description',
            DB::raw('SUM(total_amount) as total_amount'),
            //DB::raw('SUM(total_weight) as total_weight')
        )
            ->groupBy('period', 'ammunition_id')
            ->orderBy('period', 'asc')->get();

        $queryPie = $query->clone()->select(
            'ammunition_description',
            DB::raw('SUM(total_amount) as total_amount'),
            // DB::raw('SUM(total_weight) as total_weight')
        )
            ->groupBy('ammunition_description')
            ->orderBy('ammunition_description', 'asc')->get();

        $linedata = $this->FormatCollectionBarLine($queryBarLine, "ammunitions");
        $pieData = $this->FormatCollectionPie($queryPie, "ammunitions");


        return response()->json(['lineBarData' => $linedata, 'pieData' => $pieData]);
    }
}
