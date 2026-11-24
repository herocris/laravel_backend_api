<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Shared\PersistUploadedImageAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Ammunition\IndexAmmunitionRequest;
use App\Http\Requests\Api\V1\Ammunition\StoreAmmunitionRequest;
use App\Http\Requests\Api\V1\Ammunition\UpdateAmmunitionRequest;
use App\Http\Resources\Api\V1\AmmunitionResource;
use App\Models\Ammunition;
use App\Support\Query\Definitions\AmmunitionIndexDefinition;
use App\Support\Query\IndexQueryApplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class AmmunitionController extends Controller implements HasMiddleware
{
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
     * @return AnonymousResourceCollection Página de recursos con metadatos.
     */
    public function index(IndexAmmunitionRequest $request, IndexQueryApplier $index): AnonymousResourceCollection
    {
        $ammunitions = Ammunition::query();

        $items = $index->apply($ammunitions, $request->validated(), AmmunitionIndexDefinition::definition());

        return AmmunitionResource::collection($items)->withQuery($request->paginationQuery())->additional([
            'success' => true,
            'message' => 'Ammunition records retrieved successfully.',
        ]);
    }

    /**
     * Crea una munición nueva con validaciones previas.
     *
     * @param  StoreAmmunitionRequest  $request  Datos validados.
     * @return JsonResponse Munición creada.
     */
    public function store(StoreAmmunitionRequest $request, PersistUploadedImageAction $images): JsonResponse
    {
        $validated = $request->validated();
        $ammunition = $images->execute(new Ammunition, $validated, 'logo', 'ammunition', 'public');

        return (new AmmunitionResource($ammunition))->additional([
            'success' => true,
            'message' => 'Ammunition created successfully.',
        ])->response()->setStatusCode(201);
    }

    /**
     * Muestra una munición específica por ID.
     *
     * @param  Ammunition  $ammunition  Instancia objetivo.
     * @return AmmunitionResource Recurso solicitado.
     */
    public function show(Ammunition $ammunition): AmmunitionResource
    {
        return (new AmmunitionResource($ammunition))->additional([
            'success' => true,
            'message' => 'Ammunition retrieved successfully.',
        ]);
    }

    /**
     * Actualiza una munición con datos validados.
     *
     * @param  UpdateAmmunitionRequest  $request  Datos validados.
     * @param  Ammunition  $ammunition  Recurso a actualizar.
     * @return AmmunitionResource Recurso actualizado.
     */
    public function update(UpdateAmmunitionRequest $request, Ammunition $ammunition, PersistUploadedImageAction $images): AmmunitionResource
    {
        $validated = $request->validated();
        $images->execute($ammunition, $validated, 'logo', 'ammunition', 'public');

        return (new AmmunitionResource($ammunition))->additional([
            'success' => true,
            'message' => 'Ammunition updated successfully.',
        ]);
    }

    /**
     * Soft delete de la munición.
     *
     * @param  Ammunition  $ammunition  Recurso a eliminar lógicamente.
     * @return AmmunitionResource Recurso eliminado.
     */
    public function destroy(Ammunition $ammunition): AmmunitionResource
    {
        $ammunition->delete();

        return (new AmmunitionResource($ammunition))->additional([
            'success' => true,
            'message' => 'Ammunition deleted successfully.',
        ]);
    }

    /**
     * Lista registros eliminados con filtros y paginación SQL.
     *
     * @return AnonymousResourceCollection Página de recursos con metadatos.
     */
    public function indexDeleted(IndexAmmunitionRequest $request, IndexQueryApplier $index): AnonymousResourceCollection
    {
        $ammunitions = Ammunition::onlyTrashed();

        $items = $index->apply($ammunitions, $request->validated(), AmmunitionIndexDefinition::definition());

        return AmmunitionResource::collection($items)->withQuery($request->paginationQuery())->additional([
            'success' => true,
            'message' => 'Ammunition records retrieved successfully.',
        ]);
    }

    /**
     * Restaura una munición previamente eliminada.
     *
     * @param  Ammunition  $ammunition  Recurso a restaurar.
     * @return AmmunitionResource Recurso restaurado.
     */
    public function restore(Ammunition $ammunition): AmmunitionResource
    {
        $ammunition->restore();

        return (new AmmunitionResource($ammunition))->additional([
            'success' => true,
            'message' => 'Ammunition restored successfully.',
        ]);
    }
}
