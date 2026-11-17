<?php

namespace App\Http\Controllers;

use App\Helpers\PersistUploadedImage;
use App\Http\Requests\Weapon\StorePostRequest;
use App\Http\Requests\Weapon\UpdatePutRequest;
use App\Http\Resources\Weapon\WeaponResource;
use App\Models\Weapon;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class WeaponController extends ApiController implements HasMiddleware
{
    private const LIST_FIELDS = [
        'identificador' => 'id',
        'descripcion' => 'description',
        'logo' => 'logo',
    ];

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
    public function index(): JsonResponse
    {
        $weapons = Weapon::query();

        return $this->showAll($weapons, WeaponResource::class, self::LIST_FIELDS);
    }

    /**
     * Crea una nueva arma con datos validados. Side effects: eventos Eloquent.
     *
     * @param  StorePostRequest  $request  Datos validados.
     * @return JsonResponse Arma creada.
     */
    public function store(StorePostRequest $request, PersistUploadedImage $images)
    {
        $validated = $request->validated();
        $weapon = $images->save(new Weapon, $validated, 'logo', 'weapon', 'public');

        return $this->showOne(new WeaponResource($weapon));
    }

    /**
     * Muestra un arma específica por ID (binding automático).
     *
     * @param  Weapon  $weapon  Instancia objetivo.
     * @return JsonResponse Representación del arma.
     */
    public function show(Weapon $weapon)
    {
        return $this->showOne(new WeaponResource($weapon));
    }

    /**
     * Actualiza atributos permitidos de un arma.
     *
     * @param  UpdatePutRequest  $request  Datos validados.
     * @param  Weapon  $weapon  Arma a actualizar.
     * @return JsonResponse Arma actualizada.
     */
    public function update(UpdatePutRequest $request, Weapon $weapon, PersistUploadedImage $images)
    {
        $validated = $request->validated();
        $images->save($weapon, $validated, 'logo', 'weapon', 'public');

        return $this->showOne(new WeaponResource($weapon));
    }

    /**
     * Soft delete del arma (marca `deleted_at`).
     *
     * @param  Weapon  $weapon  Arma a eliminar lógicamente.
     * @return JsonResponse Arma eliminada.
     */
    public function destroy(Weapon $weapon)
    {
        $weapon->delete();

        return $this->showOne(new WeaponResource($weapon));
    }

    /**
     * Lista registros eliminados con filtros y paginación SQL.
     *
     * @return JsonResponse Página de recursos con metadatos.
     */
    public function indexDeleted(): JsonResponse
    {
        $weapons = Weapon::onlyTrashed();

        return $this->showAll($weapons, WeaponResource::class, self::LIST_FIELDS);
    }

    /**
     * Restaura un arma soft-deleted. Requiere binding con `withTrashed()`.
     *
     * @param  Weapon  $weapon  Arma a restaurar.
     * @return JsonResponse Arma restaurada.
     */
    public function restore(Weapon $weapon)
    {
        $weapon->restore();

        return $this->showOne(new WeaponResource($weapon));
    }
}
