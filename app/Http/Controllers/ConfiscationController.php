<?php

namespace App\Http\Controllers;

use App\Http\Requests\Confiscation\StorePostRequest;
use App\Http\Requests\Confiscation\UpdatePutRequest;
use App\Http\Resources\Confiscation\ConfiscationResource;
use App\Models\Confiscation;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Http\Request;

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
        $validated=$request->validated();
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
        $confiscations= Confiscation::onlyTrashed()->get();
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
