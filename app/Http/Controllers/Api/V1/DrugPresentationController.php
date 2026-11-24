<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Shared\PersistUploadedImageAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DrugPresentation\IndexDrugPresentationRequest;
use App\Http\Requests\Api\V1\DrugPresentation\StoreDrugPresentationRequest;
use App\Http\Requests\Api\V1\DrugPresentation\UpdateDrugPresentationRequest;
use App\Http\Resources\Api\V1\DrugPresentationResource;
use App\Models\DrugPresentation;
use App\Support\Http\ApiResponse;
use App\Support\Query\Definitions\DrugPresentationIndexDefinition;
use App\Support\Query\IndexQueryApplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class DrugPresentationController extends Controller implements HasMiddleware
{
    /**
     * Middlewares aplicados al controlador de presentaciones de droga.
     * Garantiza transformación consistente de la data mediante `DrugPresentationResource`.
     *
     * @return array<Middleware> Lista de middlewares.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('can:drugPresentation.view', only: ['index', 'show', 'indexDeleted']),
            new Middleware('can:drugPresentation.create', only: ['store']),
            new Middleware('can:drugPresentation.update', only: ['update', 'restore']),
            new Middleware('can:drugPresentation.delete', only: ['destroy']),
        ];
    }

    /**
     * Lista registros con filtros y paginación SQL.
     *
     * @return JsonResponse Página de recursos con metadatos.
     */
    public function index(IndexDrugPresentationRequest $request, IndexQueryApplier $index): JsonResponse
    {
        $drugPresentations = DrugPresentation::query();

        return ApiResponse::paginated($index->apply($drugPresentations, $request->validated(), DrugPresentationIndexDefinition::definition()), DrugPresentationResource::class, $request);
    }

    /**
     * Crea una nueva presentación con datos validados.
     *
     * @param  StoreDrugPresentationRequest  $request  Datos validados.
     * @return JsonResponse Recurso creado.
     */
    public function store(StoreDrugPresentationRequest $request, PersistUploadedImageAction $images): JsonResponse
    {
        $validated = $request->validated();
        $drugPresentation = $images->execute(new DrugPresentation, $validated, 'logo', 'drugPresentation', 'public');

        return ApiResponse::resource(new DrugPresentationResource($drugPresentation));
    }

    /**
     * Muestra una presentación específica.
     *
     * @param  DrugPresentation  $drugPresentation  Instancia objetivo.
     * @return JsonResponse Recurso solicitado.
     */
    public function show(DrugPresentation $drugPresentation): JsonResponse
    {
        return ApiResponse::resource(new DrugPresentationResource($drugPresentation));
    }

    /**
     * Actualiza una presentación existente.
     *
     * @param  UpdateDrugPresentationRequest  $request  Datos validados.
     * @param  DrugPresentation  $drugPresentation  Recurso a actualizar.
     * @return JsonResponse Recurso actualizado.
     */
    public function update(UpdateDrugPresentationRequest $request, DrugPresentation $drugPresentation, PersistUploadedImageAction $images): JsonResponse
    {
        $validated = $request->validated();
        $images->execute($drugPresentation, $validated, 'logo', 'drugPresentation', 'public');

        return ApiResponse::resource(new DrugPresentationResource($drugPresentation));
    }

    /**
     * Soft delete de la presentación.
     *
     * @param  DrugPresentation  $drugPresentation  Recurso a eliminar.
     * @return JsonResponse Recurso eliminado.
     */
    public function destroy(DrugPresentation $drugPresentation): JsonResponse
    {
        $drugPresentation->delete();

        return ApiResponse::resource(new DrugPresentationResource($drugPresentation));
    }

    /**
     * Lista registros eliminados con filtros y paginación SQL.
     *
     * @return JsonResponse Página de recursos con metadatos.
     */
    public function indexDeleted(IndexDrugPresentationRequest $request, IndexQueryApplier $index): JsonResponse
    {
        $drugPresentations = DrugPresentation::onlyTrashed();

        return ApiResponse::paginated($index->apply($drugPresentations, $request->validated(), DrugPresentationIndexDefinition::definition()), DrugPresentationResource::class, $request);
    }

    /**
     * Restaura una presentación eliminada.
     *
     * @param  DrugPresentation  $drugPresentation  Recurso a restaurar.
     * @return JsonResponse Recurso restaurado.
     */
    public function restore(DrugPresentation $drugPresentation): JsonResponse
    {
        $drugPresentation->restore();

        return ApiResponse::resource(new DrugPresentationResource($drugPresentation));
    }
}
