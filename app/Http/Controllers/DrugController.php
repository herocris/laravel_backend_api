<?php

namespace App\Http\Controllers;

use App\Models\Drug;
use App\Http\Requests\Drug\StorePostRequest;
use App\Http\Requests\Drug\UpdatePutRequest;
use App\Http\Resources\Drug\DrugResource;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Http\Request;

class DrugController extends ApiController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware("transformInput:" . DrugResource::class . "", only: ['store', 'update']),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $drugs = Drug::all();
        return $this->showAll($drugs);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePostRequest $request)
    {
        $validated = $request->validated();
        $drug = Drug::create($validated);
        return $this->showOne($drug);
    }

    /**
     * Display the specified resource.
     */
    public function show(Drug $drug)
    {
        return $this->showOne($drug);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePutRequest $request, Drug $drug)
    {
        $validated=$request->validated();
        $drug->update($validated);
        return $this->showOne($drug);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Drug $drug)
    {
        $drug->delete();
        return $this->showOne($drug);
    }

    public function indexDeleted()
    {
        $drugs= Drug::onlyTrashed()->get();
        return $this->showAll($drugs);
    }
    /**
     * Restore the specified resource from storage.
     */
    public function restore(Drug $drug)
    {
        $drug->restore();
        return $this->showOne($drug);
    }
}
