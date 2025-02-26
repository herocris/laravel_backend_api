<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Admin\Role\StorePostRequest;
use App\Http\Requests\Admin\Role\UpdatePutRequest;
use App\Http\Resources\Admin\Role\RoleResource;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class RoleController extends ApiController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware("transformInput:".RoleResource::class."", only: ['store','update']),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = Role::all();
        return $this->showAll($roles);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePostRequest $request)
    {
        $validated=$request->validated();
        $role = Role::create($validated);
        $role->syncPermissions(request()->permissions ?? []);
        return $this->showOne($role);
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role)
    {
        return $this->showOne($role);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePutRequest $request, Role $role)
    {
        $validated=$request->validated();
        $role->update($validated);
        $role->syncPermissions(request()->permissions ?? []);
        return $this->showOne($role);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        $role->delete();
        return $this->showOne($role);
    }

    /**
     * Display a listing of the deleted resources.
     */
    public function indexDeleted()
    {
        $roles= Role::onlyTrashed()->get();
        return $this->showAll($roles);
    }

    /**
     * Restore the specified resource from storage.
     */
    public function restore(Role $role)
    {
        $role->restore();
        return $this->showOne($role);
    }
}
