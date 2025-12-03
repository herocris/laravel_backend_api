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

/*
|--------------------------------------------------------------------------
| API Routes - Sistema de Gestión de Decomisos
|--------------------------------------------------------------------------
|
| Este archivo define todas las rutas de la API REST.
| 
| Middleware aplicado:
| - 'auth': Autenticación JWT mediante cookie HttpOnly (AuthenticateJWT)
| - 'throttle:global': Rate limiting global para prevenir abuso
|
| Todas las rutas retornan respuestas JSON con atributos transformados al español.
| Los endpoints están agrupados en:
| 1. Autenticación y Administración (Auth, Users, Roles, Permissions, Activity Logs)
| 2. Catálogos (Drugs, Weapons, Ammunition, DrugPresentation)
| 3. Decomisos (Confiscations y sus items relacionados)
| 4. Endpoints especiales (Gráficas, Mapas, Filtros por decomiso)
|
*/

/*
|--------------------------------------------------------------------------
| Grupo 1: Autenticación y Administración
|--------------------------------------------------------------------------
| Endpoints para autenticación JWT y gestión de usuarios, roles y permisos.
| Login y Register no requieren autenticación (withoutMiddleware).
| Activity logs registra todas las operaciones CRUD del sistema.
*/

Route::middleware(['auth','throttle:global'])->group(function () {
    
    // === Autenticación JWT ===
    // === Autenticación JWT ===
    Route::post('login', [AuthController::class,'login'])->withoutMiddleware(['auth'])->name('auth.login');
    Route::post('register', [AuthController::class,'register'])->withoutMiddleware(['auth'])->name('auth.register');
    Route::get('me', [AuthController::class,'me'])->name('auth.me');
    Route::post('logout', [AuthController::class,'logout'])->name('auth.logout');
    Route::post('refresh', [AuthController::class,'refresh'])->name('auth.refresh');

    // === Gestión de Usuarios ===
    // CRUD completo + soft deletes (indexDeleted, restore)
    Route::get('/user/deleted', [UserController::class, 'indexDeleted'])->name('user.indexDeleted');
    Route::post('/user/restore/{user}', [UserController::class, 'restore'])->withTrashed()->name('user.restore');
    Route::apiResource('user', UserController::class);

    // === Gestión de Permisos ===
    // CRUD completo + soft deletes
    Route::get('/permission/deleted', [PermissionController::class, 'indexDeleted'])->name('permission.indexDeleted');
    Route::post('/permission/restore/{permission}', [PermissionController::class, 'restore'])->withTrashed()->name('permission.restore');
    Route::apiResource('permission', PermissionController::class);

    // === Gestión de Roles ===
    // CRUD completo + soft deletes
    Route::get('/role/deleted', [RoleController::class, 'indexDeleted'])->name('role.indexDeleted');
    Route::post('/role/restore/{role}', [RoleController::class, 'restore'])->withTrashed()->name('role.restore');
    Route::apiResource('role', RoleController::class);

    // === Logs de Actividad ===
    // Consulta de auditoría de todas las operaciones CRUD
    // === Logs de Actividad ===
    // Consulta de auditoría de todas las operaciones CRUD
    Route::get('activity', LogActivityController::class)->name('activity.index');
});

/*
|--------------------------------------------------------------------------
| Grupo 2: Catálogos, Decomisos y Estadísticas
|--------------------------------------------------------------------------
| Endpoints para gestión de entidades del sistema (drogas, armas, municiones,
| presentaciones de drogas) y decomisos con sus items relacionados.
| 
| Todas las entidades de catálogo incluyen:
| - CRUD completo (index, store, show, update, destroy)
| - Soft deletes (indexDeleted, restore)
| 
| Los decomisos incluyen además:
| - Endpoint especial 'map' para visualización geográfica
| - Endpoints 'graphIndex' para estadísticas agregadas por período
| - Endpoints 'indexByConfiscation' para filtrar items por decomiso específico
*/

Route::middleware(['auth','throttle:global'])->group(function () {
    
    // === Catálogo: Municiones ===
    Route::get('/ammunition/deleted', [AmmunitionController::class, 'indexDeleted'])->name('ammunition.indexDeleted');
    Route::post('/ammunition/restore/{ammunition}', [AmmunitionController::class, 'restore'])->withTrashed()->name('ammunition.restore');
    Route::apiResource('ammunition', AmmunitionController::class);

    // === Decomisos Principales ===
    // Incluye endpoint especial 'map' para visualizar decomisos en mapa
    Route::get('/confiscation/deleted', [ConfiscationController::class, 'indexDeleted'])->name('confiscation.indexDeleted');
    Route::get('/confiscation/map', [ConfiscationController::class, 'mapConfiscations']);
    Route::post('/confiscation/restore/{confiscation}', [ConfiscationController::class, 'restore'])->withTrashed()->name('confiscation.restore');
    Route::apiResource('confiscation', ConfiscationController::class);

    // === Catálogo: Presentaciones de Drogas ===
    Route::get('/drugPresentation/deleted', [DrugPresentationController::class, 'indexDeleted'])->name('drugPresentation.indexDeleted');
    Route::post('/drugPresentation/restore/{drugPresentation}', [DrugPresentationController::class, 'restore'])->withTrashed()->name('drugPresentation.restore');
    Route::apiResource('drugPresentation', DrugPresentationController::class);

    // === Catálogo: Drogas ===
    Route::get('/drug/deleted', [DrugController::class, 'indexDeleted'])->name('drug.indexDeleted');
    Route::post('/drug/restore/{drug}', [DrugController::class, 'restore'])->withTrashed()->name('drug.restore');
    Route::apiResource('drug', DrugController::class);

    // === Catálogo: Armas ===
    Route::get('/weapon/deleted', [WeaponController::class, 'indexDeleted'])->name('weapon.indexDeleted');
    Route::post('/weapon/restore/{weapon}', [WeaponController::class, 'restore'])->withTrashed()->name('weapon.restore');
    Route::apiResource('weapon', WeaponController::class);

    // === Decomisos de Municiones ===
    // graphIndex: estadísticas agregadas por período (día, mes, trimestre, semestre, año, total)
    // indexByConfiscation: lista items de un decomiso específico
    Route::get('/ammunitionGraphIndex', [AmmunitionConfiscationController::class, 'graphIndex'])->name('ammunitionConfiscation.graphIndex') ;
    Route::get('/ammunitionConfiscation/{idConfiscation}/confiscation', [AmmunitionConfiscationController::class, 'indexByConfiscation'])->name('ammunitionConfiscation.indexByConfiscation')  ;
    Route::get('/ammunitionConfiscation/deleted', [AmmunitionConfiscationController::class, 'indexDeleted'])->name('ammunitionConfiscation.indexDeleted');
    Route::post('/ammunitionConfiscation/restore/{ammunitionConfiscation}', [AmmunitionConfiscationController::class, 'restore'])->withTrashed()->name('ammunitionConfiscation.restore');
    Route::apiResource('ammunitionConfiscation', AmmunitionConfiscationController::class);

    // === Decomisos de Drogas ===
    // graphIndex: estadísticas con peso y cantidad agregadas por período
    // indexByConfiscation: lista items de drogas en un decomiso específico
    Route::get('/drugGraphIndex', [DrugConfiscationController::class, 'graphIndex'])->name('drugConfiscation.graphIndex');
    Route::get('/drugConfiscation/{idConfiscation}/confiscation', [DrugConfiscationController::class, 'indexByConfiscation'])->name('drugConfiscation.indexByConfiscation');
    Route::get('/drugConfiscation/deleted', [DrugConfiscationController::class, 'indexDeleted'])->name('drugConfiscation.indexDeleted');
    Route::post('/drugConfiscation/restore/{drugConfiscation}', [DrugConfiscationController::class, 'restore'])->withTrashed()->name('drugConfiscation.restore');
    Route::apiResource('drugConfiscation', DrugConfiscationController::class);

    // === Decomisos de Armas ===
    // graphIndex: estadísticas agregadas por período
    // indexByConfiscation: lista items de armas en un decomiso específico
    Route::get('/weaponGraphIndex', [WeaponConfiscationController::class, 'graphIndex'])->name('weaponConfiscation.graphIndex');
    Route::get('/weaponConfiscation/{idConfiscation}/confiscation', [WeaponConfiscationController::class, 'indexByConfiscation'])->name('weaponConfiscation.indexByConfiscation');
    Route::get('/weaponConfiscation/deleted', [WeaponConfiscationController::class, 'indexDeleted'])->name('weaponConfiscation.indexDeleted');
    Route::post('/weaponConfiscation/restore/{weaponConfiscation}', [WeaponConfiscationController::class, 'restore'])->withTrashed()->name('weaponConfiscation.restore');
    Route::apiResource('weaponConfiscation', WeaponConfiscationController::class);
});

