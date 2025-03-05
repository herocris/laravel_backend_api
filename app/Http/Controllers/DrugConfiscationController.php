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
use Illuminate\Support\Facades\DB;

class DrugConfiscationController extends ApiController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware("transformInput:" . DrugConfiscationResource::class . "", only: ['store', 'update']),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $drugConfiscations = DrugConfiscation::all();
        return $this->showAll($drugConfiscations);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePostRequest $request)
    {
        $validated = $request->validated();
        $drugConfiscation = DrugConfiscation::create($validated);
        return $this->showOne($drugConfiscation);
    }

    /**
     * Display the specified resource.
     */
    public function show(DrugConfiscation $drugConfiscation)
    {
        return $this->showOne($drugConfiscation);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePutRequest $request, DrugConfiscation $drugConfiscation)
    {
        $validated=$request->validated();
        $drugConfiscation->update($validated);
        return $this->showOne($drugConfiscation);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DrugConfiscation $drugConfiscation)
    {
        $drugConfiscation->delete();
        return $this->showOne($drugConfiscation);
    }

    public function indexDeleted()
    {
        $drugConfiscations= DrugConfiscation::onlyTrashed()->get();
        return $this->showAll($drugConfiscations);
    }
    /**
     * Restore the specified resource from storage.
     */
    public function restore(DrugConfiscation $drugConfiscation)
    {
        $drugConfiscation->restore();
        return $this->showOne($drugConfiscation);
    }

    public function graphIndex(GetRequest $request)
    {
        $period = $request->input('period'); // Por defecto: día
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $drugs = json_decode($request->input('drugs') ?? '[]');
        $presentations =json_decode($request->input('presentations') ?? '[]');

        $queryp=$this->queryPeriod($period);

        // Construir la consulta con el período seleccionado
        $query = DB::table('data_graph_drug_confiscation')
            ->selectRaw("{$queryp} AS period, drug_id, drug_presentation_id, SUM(total_amount) AS total_amount, SUM(total_weight) AS total_weight")
            ->whereBetween('full_date', [$startDate, $endDate])
            ->whereIn('drug_id', $drugs)
            ->whereIn('drug_presentation_id', $presentations)
            ->groupBy('period', 'drug_id', 'drug_presentation_id')
            ->orderBy('period')
            ->get();

        $query = $this->queryFormater($query,$request);

        return response()->json($query);
    }
}
