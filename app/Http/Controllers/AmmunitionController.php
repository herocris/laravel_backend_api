<?php

namespace App\Http\Controllers;

use App\Http\Requests\Ammunition\StorePostRequest;
use App\Http\Requests\Ammunition\UpdatePutRequest;
use App\Http\Resources\Ammunition\AmmunitionResource;
use App\Models\Ammunition;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class AmmunitionController extends ApiController implements HasMiddleware
{
    /**
     * Middlewares del controlador de municiones.
     * `transformInput` alinea la carga útil con `AmmunitionResource` en
     * creación y actualización para mantener consistencia.
     *
     * @return array<Middleware> Lista de middlewares.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:ammunition.view,api', only: ['index', 'show', 'indexDeleted']),
            new Middleware('permission:ammunition.create,api', only: ['store']),
            new Middleware('permission:ammunition.update,api', only: ['update', 'restore']),
            new Middleware('permission:ammunition.delete,api', only: ['destroy']),
            new Middleware('transformInput:'.AmmunitionResource::class.'', only: ['store', 'update']),
        ];
    }

    /**
     * Lista todas las municiones activas. Sin paginación.
     *
     * @return JsonResponse Colección de municiones.
     */
    public function index()
    {
        $ammunitions = Ammunition::all();

        return $this->showAll($ammunitions);
    }

    /**
     * Crea una munición nueva con validaciones previas.
     *
     * @param  StorePostRequest  $request  Datos validados.
     * @return JsonResponse Munición creada.
     */
    public function store(StorePostRequest $request)
    {
        $validated = $request->validated();
        $ammunition = Ammunition::create($validated);

        return $this->showOne($ammunition);
    }

    /**
     * Muestra una munición específica por ID.
     *
     * @param  Ammunition  $ammunition  Instancia objetivo.
     * @return JsonResponse Recurso solicitado.
     */
    public function show(Ammunition $ammunition)
    {
        return $this->showOne($ammunition);
    }

    /**
     * Actualiza una munición con datos validados.
     *
     * @param  UpdatePutRequest  $request  Datos validados.
     * @param  Ammunition  $ammunition  Recurso a actualizar.
     * @return JsonResponse Recurso actualizado.
     */
    public function update(UpdatePutRequest $request, Ammunition $ammunition)
    {
        $validated = $request->validated();
        $ammunition->update($validated);

        return $this->showOne($ammunition);
    }

    /**
     * Soft delete de la munición.
     *
     * @param  Ammunition  $ammunition  Recurso a eliminar lógicamente.
     * @return JsonResponse Recurso eliminado.
     */
    public function destroy(Ammunition $ammunition)
    {
        $ammunition->delete();

        return $this->showOne($ammunition);
    }

    /**
     * Lista municiones en papelera (soft-deleted).
     *
     * @return JsonResponse Colección en papelera.
     */
    public function indexDeleted()
    {
        $ammunitions = Ammunition::onlyTrashed()->get();

        return $this->showAll($ammunitions);
    }

    /**
     * Restaura una munición previamente eliminada.
     *
     * @param  Ammunition  $ammunition  Recurso a restaurar.
     * @return JsonResponse Recurso restaurado.
     */
    public function restore(Ammunition $ammunition)
    {
        $ammunition->restore();

        return $this->showOne($ammunition);
    }
}
