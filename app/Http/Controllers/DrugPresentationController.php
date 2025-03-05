<?php

namespace App\Http\Controllers;

use App\Models\DrugPresentation;
use App\Http\Requests\DrugPresentation\StorePostRequest;
use App\Http\Requests\DrugPresentation\UpdatePutRequest;
use App\Http\Resources\DrugPresentation\DrugPresentationResource;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Http\Request;

class DrugPresentationController extends ApiController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware("transformInput:" . DrugPresentationResource::class . "", only: ['store', 'update']),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $drugPresentations = DrugPresentation::all();
        return $this->showAll($drugPresentations);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePostRequest $request)
    {
        $validated = $request->validated();
        $drugPresentation = DrugPresentation::create($validated);
        return $this->showOne($drugPresentation);
    }

    /**
     * Display the specified resource.
     */
    public function show(DrugPresentation $drugPresentation)
    {
        return $this->showOne($drugPresentation);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePutRequest $request, DrugPresentation $drugPresentation)
    {
        $validated=$request->validated();
        $drugPresentation->update($validated);
        return $this->showOne($drugPresentation);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DrugPresentation $drugPresentation)
    {
        $drugPresentation->delete();
        return $this->showOne($drugPresentation);
    }

    public function indexDeleted()
    {
        $drugPresentations= DrugPresentation::onlyTrashed()->get();
        return $this->showAll($drugPresentations);
    }
    /**
     * Restore the specified resource from storage.
     */
    public function restore(DrugPresentation $drugPresentation)
    {
        $drugPresentation->restore();
        return $this->showOne($drugPresentation);
    }
}
