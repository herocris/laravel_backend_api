<?php

namespace App\Http\Controllers;

use App\Models\WeaponConfiscation;
use Illuminate\Http\Request;
use App\Http\Requests\WeaponConfiscation\StorePostRequest;
use App\Http\Requests\WeaponConfiscation\UpdatePutRequest;
use App\Http\Resources\WeaponConfiscation\WeaponConfiscationResource;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

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
        $validated=$request->validated();
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
        $weaponConfiscations= WeaponConfiscation::onlyTrashed()->get();
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
}
