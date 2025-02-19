<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Permission;
use Illuminate\Support\Facades\Validator;

class PermissionController extends ApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $permissions=Permission::all();
        return $this->showAll($permissions);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'=> 'required|unique:permissions,name',
        ]);
        $validator->validate();

        $permission = new Permission();
        $permission = $permission->create([
            'name' => request()->name,
            'guard_name' => 'api'
        ]);
        $permission->save();

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
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Permission $permission)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
        ]);
        $validator->validate();

        $permission->update([
            'name' => request()->name,
        ]);

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
}
