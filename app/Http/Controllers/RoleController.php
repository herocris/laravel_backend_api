<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoleController extends ApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = Role::all();
        return $this->showAll($roles);
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
            'name' => 'required|unique:roles,name',
        ]);
        $validator->validate();

        $role = new Role();
        $role = $role->create([
            'name' => request()->name,
            'guard_name' => 'api'
        ]);
        $role->save();

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
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
        ]);
        $validator->validate();

        $role->update([
            'name' => request()->name,
        ]);

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
}
