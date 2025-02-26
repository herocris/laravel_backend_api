<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\LogActivityController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');


Route::middleware(['auth','throttle:global'])->group(function () {
    Route::post('login', [AuthController::class,'login'])->withoutMiddleware(['auth']);
    Route::get('me', [AuthController::class,'me']);
    Route::post('logout', [AuthController::class,'logout']);
    Route::post('refresh', [AuthController::class,'refresh']);

    Route::resource('user', UserController::class);
    Route::resource('permission', PermissionController::class);
    Route::resource('role', RoleController::class);

    Route::get('activity', LogActivityController::class);
});

