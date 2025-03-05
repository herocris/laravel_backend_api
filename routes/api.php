<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\LogActivityController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AmmunitionConfiscationController;
use App\Http\Controllers\AmmunitionController;
use App\Http\Controllers\ConfiscationController;
use App\Http\Controllers\DrugConfiscationController;
use App\Http\Controllers\DrugController;
use App\Http\Controllers\DrugPresentationController;
use App\Http\Controllers\WeaponConfiscationController;
use App\Http\Controllers\WeaponController;
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

Route::middleware(['auth','throttle:global'])->group(function () {
    Route::get('/ammunition/deleted', [AmmunitionController::class, 'indexDeleted']);
    Route::post('/ammunition/restore/{ammunition}', [AmmunitionController::class, 'restore'])->withTrashed();
    Route::apiResource('ammunition', AmmunitionController::class);

    Route::get('/confiscation/deleted', [ConfiscationController::class, 'indexDeleted']);
    Route::post('/confiscation/restore/{confiscation}', [ConfiscationController::class, 'restore'])->withTrashed();
    Route::apiResource('confiscation', ConfiscationController::class);

    Route::get('/drugPresentation/deleted', [DrugPresentationController::class, 'indexDeleted']);
    Route::post('/drugPresentation/restore/{drugPresentation}', [DrugPresentationController::class, 'restore'])->withTrashed();
    Route::apiResource('drugPresentation', DrugPresentationController::class);

    Route::get('/drug/deleted', [DrugController::class, 'indexDeleted']);
    Route::post('/drug/restore/{drug}', [DrugController::class, 'restore'])->withTrashed();
    Route::apiResource('drug', DrugController::class);

    Route::get('/weapon/deleted', [WeaponController::class, 'indexDeleted']);
    Route::post('/weapon/restore/{weapon}', [WeaponController::class, 'restore'])->withTrashed();
    Route::apiResource('weapon', WeaponController::class);

    Route::get('/ammunitionConfiscation/deleted', [AmmunitionConfiscationController::class, 'indexDeleted']);
    Route::post('/ammunitionConfiscation/restore/{ammunitionConfiscation}', [AmmunitionConfiscationController::class, 'restore'])->withTrashed();
    Route::apiResource('ammunitionConfiscation', AmmunitionConfiscationController::class);

    Route::get('/graphIndex', [DrugConfiscationController::class, 'graphIndex']);
    Route::get('/drugConfiscation/deleted', [DrugConfiscationController::class, 'indexDeleted']);
    Route::post('/drugConfiscation/restore/{drugConfiscation}', [DrugConfiscationController::class, 'restore'])->withTrashed();
    Route::apiResource('drugConfiscation', DrugConfiscationController::class);

    Route::get('/weaponConfiscation/deleted', [WeaponConfiscationController::class, 'indexDeleted']);
    Route::post('/weaponConfiscation/restore/{weaponConfiscation}', [WeaponConfiscationController::class, 'restore'])->withTrashed();
    Route::apiResource('weaponConfiscation', WeaponConfiscationController::class);
});

