<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Shared\PersistUploadedImageAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Drug\IndexDrugRequest;
use App\Http\Requests\Api\V1\Drug\StoreDrugRequest;
use App\Http\Requests\Api\V1\Drug\UpdateDrugRequest;
use App\Http\Resources\Api\V1\DrugResource;
use App\Models\Drug;
use App\Support\Query\Definitions\DrugIndexDefinition;
use App\Support\Query\IndexQueryApplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class DrugController extends Controller implements HasMiddleware
{
    /**
     * Middlewares aplicados al controlador de catálogo de drogas.
     *
     * @return array<Middleware> Lista de middlewares declarados.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('can:drug.view', only: ['index', 'show', 'indexDeleted']),
            new Middleware('can:drug.create', only: ['store']),
            new Middleware('can:drug.update', only: ['update', 'restore']),
            new Middleware('can:drug.delete', only: ['destroy']),
        ];
    }

    /**
     * Lista registros con filtros y paginación SQL.
     *
     * @return AnonymousResourceCollection Página de recursos con metadatos.
     */
    public function index(IndexDrugRequest $request, IndexQueryApplier $index): AnonymousResourceCollection
    {
        $drugs = Drug::query();

        $items = $index->apply($drugs, $request->validated(), DrugIndexDefinition::definition());

        return DrugResource::collection($items)->withQuery($request->paginationQuery())->additional([
            'success' => true,
            'message' => 'Drug records retrieved successfully.',
        ]);
    }

    /**
     * Crea una nueva droga usando datos validados por `StoreDrugRequest`.
     * Side effects: dispara eventos Eloquent (creating/created). No retorna
     * 201 al crear el recurso.
     *
     * @param  StoreDrugRequest  $request  Datos validados (descripcion, logo, etc.).
     * @return JsonResponse Recurso recién creado.
     */
    public function store(StoreDrugRequest $request, PersistUploadedImageAction $images): JsonResponse
    {
        $validated = $request->validated();
        $drug = $images->execute(new Drug, $validated, 'logo', 'drug', 'public');

        return (new DrugResource($drug))->additional([
            'success' => true,
            'message' => 'Drug created successfully.',
        ])->response()->setStatusCode(201);
    }

    /**
     * Muestra una droga específica mediante Route Model Binding.
     * Si el ID no existe Laravel responde 404 antes de entrar al método.
     *
     * @param  Drug  $drug  Instancia objetivo.
     * @return DrugResource Representación del recurso.
     */
    public function show(Drug $drug): DrugResource
    {
        return (new DrugResource($drug))->additional([
            'success' => true,
            'message' => 'Drug retrieved successfully.',
        ]);
    }

    /**
     * Actualiza atributos permitidos de una droga. Campos verificados por
     * `UpdateDrugRequest`. Retorna estado final tras persistencia.
     *
     * @param  UpdateDrugRequest  $request  Datos validados.
     * @param  Drug  $drug  Modelo a actualizar.
     * @return DrugResource Recurso actualizado.
     */
    public function update(UpdateDrugRequest $request, Drug $drug, PersistUploadedImageAction $images): DrugResource
    {
        $validated = $request->validated();
        $images->execute($drug, $validated, 'logo', 'drug', 'public');

        return (new DrugResource($drug))->additional([
            'success' => true,
            'message' => 'Drug updated successfully.',
        ]);
    }

    /**
     * Soft delete (marca `deleted_at`) de la droga. Preserva datos para
     * futura restauración y auditoría. No elimina relaciones dependientes.
     *
     * @param  Drug  $drug  Recurso a eliminar lógicamente.
     * @return DrugResource Recurso eliminado.
     */
    public function destroy(Drug $drug): DrugResource
    {
        $drug->delete();

        return (new DrugResource($drug))->additional([
            'success' => true,
            'message' => 'Drug deleted successfully.',
        ]);
    }

    /**
     * Lista registros eliminados con filtros y paginación SQL.
     *
     * @return AnonymousResourceCollection Página de recursos con metadatos.
     */
    public function indexDeleted(IndexDrugRequest $request, IndexQueryApplier $index): AnonymousResourceCollection
    {
        $drugs = Drug::onlyTrashed();

        $items = $index->apply($drugs, $request->validated(), DrugIndexDefinition::definition());

        return DrugResource::collection($items)->withQuery($request->paginationQuery())->additional([
            'success' => true,
            'message' => 'Drug records retrieved successfully.',
        ]);
    }

    /**
     * Restaura una droga previamente eliminada. Requiere que la ruta use
     * binding con `withTrashed()`. Si ya estaba activa no cambia estado.
     *
     * @param  Drug  $drug  Recurso a restaurar.
     * @return DrugResource Recurso restaurado.
     */
    public function restore(Drug $drug): DrugResource
    {
        $drug->restore();

        return (new DrugResource($drug))->additional([
            'success' => true,
            'message' => 'Drug restored successfully.',
        ]);
    }
}
