<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Admin\User\StorePostRequest;
use App\Http\Requests\Admin\User\UpdatePutRequest;
use App\Http\Resources\Admin\User\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;


class UserController extends ApiController implements HasMiddleware
{
    
    public static function middleware(): array
    {
        return [
            new Middleware("transformInput:" . UserResource::class . "", only: ['store', 'update']),
        ];
    }

    public function index()
    {
        $users = User::all();
        return $this->showAll($users);
    }


    public function store(StorePostRequest $request)
    {
        $validated = $request->validated();
        $user = User::create($validated);
        return $this->showOne($user);
    }

    public function show(User $user)
    {
        return $this->showOne($user);
    }


    public function update(UpdatePutRequest $request, User $user)
    {
        $validated=$request->validated();
        $user->update($validated);
        return $this->showOne($user);
    }

    public function destroy(User $user)
    {
        $user->delete();
        return $this->showOne($user);
    }

    public function indexDeleted()
    {
        $users= User::onlyTrashed()->get();
        return $this->showAll($users);
    }

    public function restore(User $user)
    {
        $user->restore();
        return $this->showOne($user);
    }
}
