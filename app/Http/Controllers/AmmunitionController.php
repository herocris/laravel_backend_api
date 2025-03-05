<?php

namespace App\Http\Controllers;

use App\Models\Ammunition;
use Illuminate\Http\Request;
use App\Http\Requests\Ammunition\StorePostRequest;
use App\Http\Requests\Ammunition\UpdatePutRequest;
use App\Http\Resources\Ammunition\AmmunitionResource;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class AmmunitionController extends ApiController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware("transformInput:" . AmmunitionResource::class . "", only: ['store', 'update']),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $ammunitions = Ammunition::all();
        return $this->showAll($ammunitions);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePostRequest $request)
    {
        $validated = $request->validated();
        $ammunition = Ammunition::create($validated);
        return $this->showOne($ammunition);
    }

    /**
     * Display the specified resource.
     */
    public function show(Ammunition $ammunition)
    {
        return $this->showOne($ammunition);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePutRequest $request, Ammunition $ammunition)
    {
        $validated=$request->validated();
        $ammunition->update($validated);
        return $this->showOne($ammunition);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ammunition $ammunition)
    {
        $ammunition->delete();
        return $this->showOne($ammunition);
    }

    public function indexDeleted()
    {
        $ammunitions= Ammunition::onlyTrashed()->get();
        return $this->showAll($ammunitions);
    }
    /**
     * Restore the specified resource from storage.
     */
    public function restore(Ammunition $ammunition)
    {
        $ammunition->restore();
        return $this->showOne($ammunition);
    }
}
