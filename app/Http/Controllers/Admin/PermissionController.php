<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Admin\Permission\StorePostRequest;
use App\Http\Requests\Admin\Permission\UpdatePutRequest;
use App\Http\Resources\Admin\Permission\PermissionResource;
use Illuminate\Http\Request;
use App\Models\Permission;
use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PermissionController extends ApiController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware("transformInput:".PermissionResource::class."", only: ['store','update']),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $permissions=Permission::all();
        return $this->showAll($permissions);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePostRequest $request)
    {
        $validated = $request->validated();
        $permission = Permission::create($validated);
        return $this->showOne($permission);
    }

    /**
     * Display the specified resource.
     */
    public function show(Permission $permission)
    {
        return $this->showOne($permission);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePutRequest $request, Permission $permission)
    {
        $validated=$request->validated();
        $permission->update($validated);
        return $this->showOne($permission);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Permission $permission)
    {
        $permission->delete();
        return $this->showOne($permission);
    }
    /**
     * Display a listing of the deleted resources.
     */
    public function indexDeleted()
    {
        $permissions= Permission::onlyTrashed()->get();
        return $this->showAll($permissions);
    }
    /**
     * Restore the specified resource from storage.
     */
    public function restore(Permission $permission)
    {
        $permission->restore();
        return $this->showOne($permission);
    }
}
