<?php

namespace App\Http\Controllers;

use App\Http\Requests\Confiscation\StorePostRequest;
use App\Http\Requests\Confiscation\UpdatePutRequest;
use App\Http\Requests\Confiscation\GetRequest;
use App\Http\Resources\Confiscation\ConfiscationResource;
use App\Models\Ammunition;
use App\Models\Confiscation;
use App\Models\Drug;
use App\Models\Weapon;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConfiscationController extends ApiController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware("transformInput:" . ConfiscationResource::class . "", only: ['store', 'update']),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $confiscations = Confiscation::all();
        return $this->showAll($confiscations);
    }
    public function mapConfiscations(GetRequest $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $drugs = json_decode($request->input('drugs') ?? '[]');
        $weapons = json_decode($request->input('weapons') ?? '[]');
        $ammunitions = json_decode($request->input('ammunitions') ?? '[]');


        // $confiscations = DB::table('confiscations')
        //     ->select(
        //         'confiscations.id',
        //         'confiscations.date',
        //         'confiscations.observation',
        //         'confiscations.latitude',
        //         'confiscations.length'
        //     )
        //     ->get()
        //     ->map(function ($confiscation) {
        //         // 🔹 Drogas
        //         $drugs = DB::table('drug_confiscations')
        //             ->join('drugs', 'drugs.id', '=', 'drug_confiscations.drug_id')
        //             ->where('drug_confiscations.confiscation_id', $confiscation->id)
        //             ->select(
        //                 'drug_confiscations.amount',
        //                 'drug_confiscations.weight',
        //                 'drugs.description',
        //                 'drugs.logo'
        //             )
        //             ->get();

        //         // 🔹 Armas
        //         $weapons = DB::table('weapon_confiscations')
        //             ->join('weapons', 'weapons.id', '=', 'weapon_confiscations.weapon_id')
        //             ->where('weapon_confiscations.confiscation_id', $confiscation->id)
        //             ->select(
        //                 'weapon_confiscations.amount',
        //                 'weapons.description',
        //                 'weapons.logo'
        //             )
        //             ->get();

        //         // 🔹 Municiones
        //         $ammunitions = DB::table('ammunition_confiscations')
        //             ->join('ammunitions', 'ammunitions.id', '=', 'ammunition_confiscations.ammunition_id')
        //             ->where('ammunition_confiscations.confiscation_id', $confiscation->id)
        //             ->select(
        //                 'ammunition_confiscations.amount',
        //                 'ammunitions.description',
        //                 'ammunitions.logo'
        //             )
        //             ->get();

        //         // 🔹 Agregar los subarreglos al objeto principal
        //         $confiscation->drugs = $drugs;
        //         $confiscation->weapons = $weapons;
        //         $confiscation->ammunitions = $ammunitions;

        //         return $confiscation;
        //     });



        $confiscations = Confiscation::select('id', 'date as fecha', 'latitude as latitud','observation as observacion', 'length as longitud')
            ->with([
                'drugConfiscations' => function ($query) use ($drugs) {
                    $query->select('id', 'confiscation_id', 'confiscation_id as decomiso','photo as foto', 'amount as cantidad', 'weight as peso', 'drug_id', 'drug_id as droga')
                        ->addSelect([
                            'droga_nombre' => Drug::select('description')
                                ->whereColumn('drugs.id', 'drug_confiscations.drug_id')
                                ->limit(1),
                        ])
                        //->with(['drug:id,description,logo'])
                        ->whereIn('drug_id', $drugs);
                },
                'weaponConfiscations' => function ($query) use ($weapons) {
                    $query->select('id', 'confiscation_id', 'confiscation_id as decomiso','photo as foto', 'amount as cantidad', 'weapon_id', 'weapon_id as arma')
                        ->addSelect([
                            'arma_nombre' => Weapon::select('description')
                                ->whereColumn('weapons.id', 'weapon_confiscations.weapon_id')
                                ->limit(1),
                        ])
                        //->with(['weapon:id,description,logo'])
                        ->whereIn('weapon_id', $weapons);
                },
                'ammunitionConfiscations' => function ($query) use ($ammunitions) {
                    $query->select('id', 'confiscation_id', 'confiscation_id as decomiso','photo as foto', 'amount as cantidad', 'ammunition_id', 'ammunition_id as municion')
                        ->addSelect([
                            'municion_nombre' => Ammunition::select('description')
                                ->whereColumn('ammunitions.id', 'ammunition_confiscations.ammunition_id')
                                ->limit(1),
                        ])
                        //->with(['ammunition:id,description,logo'])
                        ->whereIn('ammunition_id', $ammunitions);
                },
            ])
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $confiscations = $confiscations->filter(function ($c) {
            return $c->drugConfiscations->isNotEmpty() ||
                $c->weaponConfiscations->isNotEmpty() ||
                $c->ammunitionConfiscations->isNotEmpty();
        })->values()
            ->toArray();
        //dd($confiscations[0]);

        return response()->json(['mapItems' => $confiscations]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePostRequest $request)
    {
        //dd($request->all());
        $validated = $request->validated();
        $confiscation = Confiscation::create($validated);
        return $this->showOne($confiscation);
    }

    /**
     * Display the specified resource.
     */
    public function show(Confiscation $confiscation)
    {
        return $this->showOne($confiscation);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePutRequest $request, Confiscation $confiscation)
    {
        $validated = $request->validated();
        $confiscation->update($validated);
        return $this->showOne($confiscation);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Confiscation $confiscation)
    {
        $confiscation->delete();
        return $this->showOne($confiscation);
    }

    public function indexDeleted()
    {
        $confiscations = Confiscation::onlyTrashed()->get();
        return $this->showAll($confiscations);
    }
    /**
     * Restore the specified resource from storage.
     */
    public function restore(Confiscation $confiscation)
    {
        $confiscation->restore();
        return $this->showOne($confiscation);
    }
}
