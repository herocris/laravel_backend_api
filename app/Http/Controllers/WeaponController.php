<?php

namespace App\Http\Controllers;

use App\Models\Weapon;
use Illuminate\Http\Request;
use App\Http\Requests\Weapon\StorePostRequest;
use App\Http\Requests\Weapon\UpdatePutRequest;
use App\Http\Resources\Weapon\WeaponResource;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class WeaponController extends ApiController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware("transformInput:" . WeaponResource::class . "", only: ['store', 'update']),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $weapons = Weapon::all();
        return $this->showAll($weapons);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePostRequest $request)
    {
        $validated = $request->validated();
        $weapon = Weapon::create($validated);
        return $this->showOne($weapon);
    }

    /**
     * Display the specified resource.
     */
    public function show(Weapon $weapon)
    {
        return $this->showOne($weapon);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePutRequest $request, Weapon $weapon)
    {
        $validated=$request->validated();
        $weapon->update($validated);
        return $this->showOne($weapon);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Weapon $weapon)
    {
        $weapon->delete();
        return $this->showOne($weapon);
    }

    public function indexDeleted()
    {
        $weapons= Weapon::onlyTrashed()->get();
        return $this->showAll($weapons);
    }
    /**
     * Restore the specified resource from storage.
     */
    public function restore(Weapon $weapon)
    {
        $weapon->restore();
        return $this->showOne($weapon);
    }
}
