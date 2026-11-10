<?php

namespace App\Http\Controllers;

use App\Http\Requests\Weapon\StorePostRequest;
use App\Http\Requests\Weapon\UpdatePutRequest;
use App\Http\Resources\Weapon\WeaponResource;
use App\Models\Weapon;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class WeaponController extends ApiController implements HasMiddleware
{
    /**
     * Declara middlewares aplicados al controlador de armas.
     * `transformInput` mantiene coherencia entre estructura de entrada y
     * el `WeaponResource` para operaciones de creación y actualización.
     *
     * @return array<Middleware> Lista de middlewares.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:weapon.view,api', only: ['index', 'show', 'indexDeleted']),
            new Middleware('permission:weapon.create,api', only: ['store']),
            new Middleware('permission:weapon.update,api', only: ['update', 'restore']),
            new Middleware('permission:weapon.delete,api', only: ['destroy']),
            new Middleware('transformInput:'.WeaponResource::class.'', only: ['store', 'update']),
        ];
    }

    /**
     * Lista todas las armas registradas (sin paginación).
     * Recomendado paginar a futuro para grandes volúmenes.
     *
     * @return JsonResponse Colección de armas activas.
     */
    public function index()
    {
        $weapons = Weapon::all();

        return $this->showAll($weapons);
    }

    /**
     * Crea una nueva arma con datos validados. Side effects: eventos Eloquent.
     *
     * @param  StorePostRequest  $request  Datos validados.
     * @return JsonResponse Arma creada.
     */
    public function store(StorePostRequest $request)
    {
        $validated = $request->validated();
        $weapon = Weapon::create($validated);

        return $this->showOne($weapon);
    }

    /**
     * Muestra un arma específica por ID (binding automático).
     *
     * @param  Weapon  $weapon  Instancia objetivo.
     * @return JsonResponse Representación del arma.
     */
    public function show(Weapon $weapon)
    {
        return $this->showOne($weapon);
    }

    /**
     * Actualiza atributos permitidos de un arma.
     *
     * @param  UpdatePutRequest  $request  Datos validados.
     * @param  Weapon  $weapon  Arma a actualizar.
     * @return JsonResponse Arma actualizada.
     */
    public function update(UpdatePutRequest $request, Weapon $weapon)
    {
        $validated = $request->validated();
        $weapon->update($validated);

        return $this->showOne($weapon);
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

        return $this->showOne($weapon);
    }

    /**
     * Lista armas soft-deleted para recuperación.
     *
     * @return JsonResponse Colección en papelera.
     */
    public function indexDeleted()
    {
        $weapons = Weapon::onlyTrashed()->get();

        return $this->showAll($weapons);
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

        return $this->showOne($weapon);
    }
}
