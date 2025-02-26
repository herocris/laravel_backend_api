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

    Route::get('/user/deleted', [UserController::class, 'indexDeleted']);
    Route::post('/user/restore/{user}', [UserController::class, 'restore'])->withTrashed();
    Route::apiResource('user', UserController::class);

    Route::get('/permission/deleted', [PermissionController::class, 'indexDeleted']);
    Route::post('/permission/restore/{permission}', [PermissionController::class, 'restore'])->withTrashed();
    Route::apiResource('permission', PermissionController::class);

    Route::get('/role/deleted', [RoleController::class, 'indexDeleted']);
    Route::post('/role/restore/{role}', [RoleController::class, 'restore'])->withTrashed();
    Route::apiResource('role', RoleController::class);

    Route::get('activity', LogActivityController::class);
});

