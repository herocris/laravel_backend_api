<?php

namespace App\Http\Controllers;

use App\Helpers\PersistUploadedImage;
use App\Http\Requests\DrugPresentation\StorePostRequest;
use App\Http\Requests\DrugPresentation\UpdatePutRequest;
use App\Http\Resources\DrugPresentation\DrugPresentationResource;
use App\Models\DrugPresentation;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class DrugPresentationController extends ApiController implements HasMiddleware
{
    private const LIST_FIELDS = [
        'identificador' => 'id',
        'descripcion' => 'description',
        'logo' => 'logo',
    ];

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
    public function index(): JsonResponse
    {
        $drugPresentations = DrugPresentation::query();

        return $this->showAll($drugPresentations, DrugPresentationResource::class, self::LIST_FIELDS);
    }

    /**
     * Crea una nueva presentación con datos validados.
     *
     * @param  StorePostRequest  $request  Datos validados.
     * @return JsonResponse Recurso creado.
     */
    public function store(StorePostRequest $request, PersistUploadedImage $images)
    {
        $validated = $request->validated();
        $drugPresentation = $images->save(new DrugPresentation, $validated, 'logo', 'drugPresentation', 'public');

        return $this->showOne(new DrugPresentationResource($drugPresentation));
    }

    /**
     * Muestra una presentación específica.
     *
     * @param  DrugPresentation  $drugPresentation  Instancia objetivo.
     * @return JsonResponse Recurso solicitado.
     */
    public function show(DrugPresentation $drugPresentation)
    {
        return $this->showOne(new DrugPresentationResource($drugPresentation));
    }

    /**
     * Actualiza una presentación existente.
     *
     * @param  UpdatePutRequest  $request  Datos validados.
     * @param  DrugPresentation  $drugPresentation  Recurso a actualizar.
     * @return JsonResponse Recurso actualizado.
     */
    public function update(UpdatePutRequest $request, DrugPresentation $drugPresentation, PersistUploadedImage $images)
    {
        $validated = $request->validated();
        $images->save($drugPresentation, $validated, 'logo', 'drugPresentation', 'public');

        return $this->showOne(new DrugPresentationResource($drugPresentation));
    }

    /**
     * Soft delete de la presentación.
     *
     * @param  DrugPresentation  $drugPresentation  Recurso a eliminar.
     * @return JsonResponse Recurso eliminado.
     */
    public function destroy(DrugPresentation $drugPresentation)
    {
        $drugPresentation->delete();

        return $this->showOne(new DrugPresentationResource($drugPresentation));
    }

    /**
     * Lista registros eliminados con filtros y paginación SQL.
     *
     * @return JsonResponse Página de recursos con metadatos.
     */
    public function indexDeleted(): JsonResponse
    {
        $drugPresentations = DrugPresentation::onlyTrashed();

        return $this->showAll($drugPresentations, DrugPresentationResource::class, self::LIST_FIELDS);
    }

    /**
     * Restaura una presentación eliminada.
     *
     * @param  DrugPresentation  $drugPresentation  Recurso a restaurar.
     * @return JsonResponse Recurso restaurado.
     */
    public function restore(DrugPresentation $drugPresentation)
    {
        $drugPresentation->restore();

        return $this->showOne(new DrugPresentationResource($drugPresentation));
    }
}
