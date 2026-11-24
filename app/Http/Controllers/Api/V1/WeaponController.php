<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Shared\PersistUploadedImageAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Weapon\IndexWeaponRequest;
use App\Http\Requests\Api\V1\Weapon\StoreWeaponRequest;
use App\Http\Requests\Api\V1\Weapon\UpdateWeaponRequest;
use App\Http\Resources\Api\V1\WeaponResource;
use App\Models\Weapon;
use App\Support\Query\Definitions\WeaponIndexDefinition;
use App\Support\Query\IndexQueryApplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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
     * @return AnonymousResourceCollection Página de recursos con metadatos.
     */
    public function index(IndexWeaponRequest $request, IndexQueryApplier $index): AnonymousResourceCollection
    {
        $weapons = Weapon::query();

        $items = $index->apply($weapons, $request->validated(), WeaponIndexDefinition::definition());

        return WeaponResource::collection($items)->withQuery($request->paginationQuery())->additional([
            'success' => true,
            'message' => 'Weapon records retrieved successfully.',
        ]);
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

        return (new WeaponResource($weapon))->additional([
            'success' => true,
            'message' => 'Weapon created successfully.',
        ])->response()->setStatusCode(201);
    }

    /**
     * Muestra un arma específica por ID (binding automático).
     *
     * @param  Weapon  $weapon  Instancia objetivo.
     * @return WeaponResource Representación del arma.
     */
    public function show(Weapon $weapon): WeaponResource
    {
        return (new WeaponResource($weapon))->additional([
            'success' => true,
            'message' => 'Weapon retrieved successfully.',
        ]);
    }

    /**
     * Actualiza atributos permitidos de un arma.
     *
     * @param  UpdateWeaponRequest  $request  Datos validados.
     * @param  Weapon  $weapon  Arma a actualizar.
     * @return WeaponResource Arma actualizada.
     */
    public function update(UpdateWeaponRequest $request, Weapon $weapon, PersistUploadedImageAction $images): WeaponResource
    {
        $validated = $request->validated();
        $images->execute($weapon, $validated, 'logo', 'weapon', 'public');

        return (new WeaponResource($weapon))->additional([
            'success' => true,
            'message' => 'Weapon updated successfully.',
        ]);
    }

    /**
     * Soft delete del arma (marca `deleted_at`).
     *
     * @param  Weapon  $weapon  Arma a eliminar lógicamente.
     * @return WeaponResource Arma eliminada.
     */
    public function destroy(Weapon $weapon): WeaponResource
    {
        $weapon->delete();

        return (new WeaponResource($weapon))->additional([
            'success' => true,
            'message' => 'Weapon deleted successfully.',
        ]);
    }

    /**
     * Lista registros eliminados con filtros y paginación SQL.
     *
     * @return AnonymousResourceCollection Página de recursos con metadatos.
     */
    public function indexDeleted(IndexWeaponRequest $request, IndexQueryApplier $index): AnonymousResourceCollection
    {
        $weapons = Weapon::onlyTrashed();

        $items = $index->apply($weapons, $request->validated(), WeaponIndexDefinition::definition());

        return WeaponResource::collection($items)->withQuery($request->paginationQuery())->additional([
            'success' => true,
            'message' => 'Weapon records retrieved successfully.',
        ]);
    }

    /**
     * Restaura un arma soft-deleted. Requiere binding con `withTrashed()`.
     *
     * @param  Weapon  $weapon  Arma a restaurar.
     * @return WeaponResource Arma restaurada.
     */
    public function restore(Weapon $weapon): WeaponResource
    {
        $weapon->restore();

        return (new WeaponResource($weapon))->additional([
            'success' => true,
            'message' => 'Weapon restored successfully.',
        ]);
    }
}
