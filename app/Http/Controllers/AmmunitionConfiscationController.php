<?php

namespace App\Http\Controllers;

use App\Models\AmmunitionConfiscation;
use Illuminate\Http\Request;
use App\Http\Requests\AmmunitionConfiscation\StorePostRequest;
use App\Http\Requests\AmmunitionConfiscation\UpdatePutRequest;
use App\Http\Resources\AmmunitionConfiscation\AmmunitionConfiscationResource;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

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
        $validated=$request->validated();
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
        $ammunitionConfiscations= AmmunitionConfiscation::onlyTrashed()->get();
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
}
