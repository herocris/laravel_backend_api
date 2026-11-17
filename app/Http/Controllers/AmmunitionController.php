<?php

namespace App\Http\Controllers;

use App\Helpers\PersistUploadedImage;
use App\Http\Requests\Ammunition\StorePostRequest;
use App\Http\Requests\Ammunition\UpdatePutRequest;
use App\Http\Resources\Ammunition\AmmunitionResource;
use App\Models\Ammunition;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class AmmunitionController extends ApiController implements HasMiddleware
{
    private const LIST_FIELDS = [
        'identificador' => 'id',
        'descripcion' => 'description',
        'logo' => 'logo',
    ];

    /**
     * Middlewares del controlador de municiones.
     *
     * @return array<Middleware> Lista de middlewares.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('can:ammunition.view', only: ['index', 'show', 'indexDeleted']),
            new Middleware('can:ammunition.create', only: ['store']),
            new Middleware('can:ammunition.update', only: ['update', 'restore']),
            new Middleware('can:ammunition.delete', only: ['destroy']),
        ];
    }

    /**
     * Lista registros con filtros y paginación SQL.
     *
     * @return JsonResponse Página de recursos con metadatos.
     */
    public function index(): JsonResponse
    {
        $ammunitions = Ammunition::query();

        return $this->showAll($ammunitions, AmmunitionResource::class, self::LIST_FIELDS);
    }

    /**
     * Crea una munición nueva con validaciones previas.
     *
     * @param  StorePostRequest  $request  Datos validados.
     * @return JsonResponse Munición creada.
     */
    public function store(StorePostRequest $request, PersistUploadedImage $images)
    {
        $validated = $request->validated();
        $ammunition = $images->save(new Ammunition, $validated, 'logo', 'ammunition', 'public');

        return $this->showOne(new AmmunitionResource($ammunition));
    }

    /**
     * Muestra una munición específica por ID.
     *
     * @param  Ammunition  $ammunition  Instancia objetivo.
     * @return JsonResponse Recurso solicitado.
     */
    public function show(Ammunition $ammunition)
    {
        return $this->showOne(new AmmunitionResource($ammunition));
    }

    /**
     * Actualiza una munición con datos validados.
     *
     * @param  UpdatePutRequest  $request  Datos validados.
     * @param  Ammunition  $ammunition  Recurso a actualizar.
     * @return JsonResponse Recurso actualizado.
     */
    public function update(UpdatePutRequest $request, Ammunition $ammunition, PersistUploadedImage $images)
    {
        $validated = $request->validated();
        $images->save($ammunition, $validated, 'logo', 'ammunition', 'public');

        return $this->showOne(new AmmunitionResource($ammunition));
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

        return $this->showOne(new AmmunitionResource($ammunition));
    }

    /**
     * Lista registros eliminados con filtros y paginación SQL.
     *
     * @return JsonResponse Página de recursos con metadatos.
     */
    public function indexDeleted(): JsonResponse
    {
        $ammunitions = Ammunition::onlyTrashed();

        return $this->showAll($ammunitions, AmmunitionResource::class, self::LIST_FIELDS);
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

        return $this->showOne(new AmmunitionResource($ammunition));
    }
}
