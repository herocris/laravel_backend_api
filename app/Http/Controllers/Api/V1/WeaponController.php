<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Shared\PersistUploadedImageAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Weapon\IndexWeaponRequest;
use App\Http\Requests\Api\V1\Weapon\StoreWeaponRequest;
use App\Http\Requests\Api\V1\Weapon\UpdateWeaponRequest;
use App\Http\Resources\Api\V1\WeaponResource;
use App\Models\Weapon;
use App\Support\Http\ApiResponse;
use App\Support\Query\Definitions\WeaponIndexDefinition;
use App\Support\Query\IndexQueryApplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class WeaponController extends Controller implements HasMiddleware
{
    /**
     * Declara middlewares aplicados al controlador de armas.
     *
     * @return array<Middleware> Lista de middlewares.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('can:weapon.view', only: ['index', 'show', 'indexDeleted']),
            new Middleware('can:weapon.create', only: ['store']),
            new Middleware('can:weapon.update', only: ['update', 'restore']),
            new Middleware('can:weapon.delete', only: ['destroy']),
        ];
    }

    /**
     * Lista registros con filtros y paginación SQL.
     *
     * @return JsonResponse Página de recursos con metadatos.
     */
    public function index(IndexWeaponRequest $request, IndexQueryApplier $index): JsonResponse
    {
        $weapons = Weapon::query();

        return ApiResponse::paginated($index->apply($weapons, $request->validated(), WeaponIndexDefinition::definition()), WeaponResource::class, $request);
    }

    /**
     * Crea una nueva arma con datos validados. Side effects: eventos Eloquent.
     *
     * @param  StoreWeaponRequest  $request  Datos validados.
     * @return JsonResponse Arma creada.
     */
    public function store(StoreWeaponRequest $request, PersistUploadedImageAction $images): JsonResponse
    {
        $validated = $request->validated();
        $weapon = $images->execute(new Weapon, $validated, 'logo', 'weapon', 'public');

        return ApiResponse::resource(new WeaponResource($weapon));
    }

    /**
     * Muestra un arma específica por ID (binding automático).
     *
     * @param  Weapon  $weapon  Instancia objetivo.
     * @return JsonResponse Representación del arma.
     */
    public function show(Weapon $weapon): JsonResponse
    {
        return ApiResponse::resource(new WeaponResource($weapon));
    }

    /**
     * Actualiza atributos permitidos de un arma.
     *
     * @param  UpdateWeaponRequest  $request  Datos validados.
     * @param  Weapon  $weapon  Arma a actualizar.
     * @return JsonResponse Arma actualizada.
     */
    public function update(UpdateWeaponRequest $request, Weapon $weapon, PersistUploadedImageAction $images): JsonResponse
    {
        $validated = $request->validated();
        $images->execute($weapon, $validated, 'logo', 'weapon', 'public');

        return ApiResponse::resource(new WeaponResource($weapon));
    }

    /**
     * Soft delete del arma (marca `deleted_at`).
     *
     * @param  Weapon  $weapon  Arma a eliminar lógicamente.
     * @return JsonResponse Arma eliminada.
     */
    public function destroy(Weapon $weapon): JsonResponse
    {
        $weapon->delete();

        return ApiResponse::resource(new WeaponResource($weapon));
    }

    /**
     * Lista registros eliminados con filtros y paginación SQL.
     *
     * @return JsonResponse Página de recursos con metadatos.
     */
    public function indexDeleted(IndexWeaponRequest $request, IndexQueryApplier $index): JsonResponse
    {
        $weapons = Weapon::onlyTrashed();

        return ApiResponse::paginated($index->apply($weapons, $request->validated(), WeaponIndexDefinition::definition()), WeaponResource::class, $request);
    }

    /**
     * Restaura un arma soft-deleted. Requiere binding con `withTrashed()`.
     *
     * @param  Weapon  $weapon  Arma a restaurar.
     * @return JsonResponse Arma restaurada.
     */
    public function restore(Weapon $weapon): JsonResponse
    {
        $weapon->restore();

        return ApiResponse::resource(new WeaponResource($weapon));
    }
}
